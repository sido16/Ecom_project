
from flask import Flask, request, jsonify
import os
import logging
import numpy as np
import faiss
import json  # Added for JSON parsing
from werkzeug.utils import secure_filename
from model.feature_extractor import extract_features
from pymysql import connect
from pymysql.cursors import DictCursor

app = Flask(__name__)

# Configure logging
logging.basicConfig(filename='flask.log', level=logging.INFO, format='%(asctime)s [%(levelname)s] %(message)s')

UPLOAD_FOLDER = 'uploads'
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

@app.route('/extract-features', methods=['POST'])
def extract():
    logging.info('Received request to /extract-features')
    logging.info('Request headers: %s', dict(request.headers))
    logging.info('Request files: %s', list(request.files.keys()))
    logging.info('Request form: %s', dict(request.form))

    image_files = request.files.getlist('images')
    image_ids = request.form.getlist('image_ids[]')  # Correct field name to match Laravel's format

    logging.info('Number of images received: %s', len(image_files))
    logging.info('Image filenames: %s', [file.filename for file in image_files if file.filename])
    logging.info('Number of image IDs received: %s', len(image_ids))
    logging.info('Image IDs: %s', image_ids)

    if 'images' not in request.files or not image_files:
        logging.error('No image files provided')
        return jsonify({'error': 'No image files provided'}), 400

    if len(image_files) != len(image_ids):
        logging.error('Mismatch between number of images and IDs: %s images, %s IDs', len(image_files), len(image_ids))
        return jsonify({'error': 'Number of images and IDs must match'}), 400

    features_map = {}
    for file, image_id in zip(image_files, image_ids):
        if file.filename == '':
            logging.warning('Skipping empty filename for image ID: %s', image_id)
            continue
        filename = secure_filename(file.filename)
        filepath = os.path.join(UPLOAD_FOLDER, filename)
        file.save(filepath)

        try:
            logging.info('Extracting features for image ID: %s, filepath: %s', image_id, filepath)
            features = extract_features(filepath)
            features_map[image_id] = features.tolist()
            logging.info('Features extracted for image ID: %s', image_id)
        except Exception as e:
            logging.error('Failed to extract features for image ID: %s, error: %s', image_id, str(e))
            return jsonify({'error': f'Failed to extract features for image ID {image_id}: {str(e)}'}), 500
        finally:
            if os.path.exists(filepath):
                os.remove(filepath)
                logging.info('Cleaned up file: %s', filepath)

    logging.info('Feature extraction completed: %s', features_map)
    return jsonify({'features': features_map})

# Database configuration (adjusted values)
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'easy_com',
    'cursorclass': DictCursor
}

# Initialize FAISS index
def initialize_faiss_index():
    try:
        conn = connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("SELECT image_id, features FROM product_features")
        rows = cursor.fetchall()
        logging.info('Database query returned %d rows', len(rows))
        conn.close()

        if not rows:
            logging.warning('No features found in product_features table')
            return None, None

        # Parse JSON strings into lists before converting to NumPy arrays
        feature_list = []
        image_ids = []
        for row in rows:
            try:
                features = json.loads(row['features']) if isinstance(row['features'], str) else row['features']
                feature_list.append(np.array(features, dtype='float32'))
                image_ids.append(row['image_id'])
            except (json.JSONDecodeError, ValueError) as e:
                logging.error('Failed to parse features for image_id %s: %s', row['image_id'], str(e))
                continue

        if not feature_list:
            logging.warning('No valid features to build FAISS index')
            return None, None

        feature_array = np.array(feature_list)
        dimension = feature_array.shape[1]
        index = faiss.IndexFlatL2(dimension)
        index.add(feature_array)

        logging.info(f'FAISS index initialized with {index.ntotal} vectors of dim {dimension}')
        return index, image_ids
    except Exception as e:
        logging.error(f'Failed to initialize FAISS index: {str(e)}')
        return None, None

# Global variables for FAISS index and image IDs
index, image_ids = initialize_faiss_index()

@app.route('/rebuild-index', methods=['POST'])
def rebuild_index():
    global index, image_ids
    logging.info('Received request to /rebuild-index')
    new_index, new_image_ids = initialize_faiss_index()
    if new_index is None:
        return jsonify({'error': 'Failed to rebuild FAISS index'}), 500
    index, image_ids = new_index, new_image_ids
    return jsonify({'message': 'FAISS index rebuilt successfully', 'vector_count': index.ntotal})

@app.route('/search', methods=['POST'])
def search():
    logging.info('Received request to /search')
    if 'image' not in request.files:
        logging.error('No image file provided')
        return jsonify({'error': 'No image file provided'}), 400

    file = request.files['image']
    if file.filename == '':
        logging.error('Empty filename provided')
        return jsonify({'error': 'Empty filename provided'}), 400

    filename = secure_filename(file.filename)
    filepath = os.path.join(UPLOAD_FOLDER, filename)
    file.save(filepath)

    try:
        logging.info('Extracting features for query image: %s', filepath)
        query_vector = extract_features(filepath).reshape(1, -1).astype('float32')
        if index is None or index.ntotal == 0:
            logging.error('FAISS index not initialized or empty')
            return jsonify({'error': 'FAISS index not available'}), 500

        k = min(5, index.ntotal)  # Return up to 5 results or all if less than 5
        distances, indices = index.search(query_vector, k)
        similar_image_ids = [image_ids[i] for i in indices[0]]

        logging.info('Search completed: %s similar images found', len(similar_image_ids))
        return jsonify({'similar_image_ids': similar_image_ids})
    except Exception as e:
        logging.error('Search failed: %s', str(e))
        return jsonify({'error': str(e)}), 500
    finally:
        if os.path.exists(filepath):
            os.remove(filepath)
            logging.info('Cleaned up file: %s', filepath)

if __name__ == '__main__':
    app.run(debug=True)