<?php

namespace App\Http\Controllers;

use App\Models\Workspace;

class WorkspaceSaveController extends SaveController
{
    public function __construct()
    {
        $this->model = Workspace::class;
        $this->relationName = 'savedWorkspaces';
        $this->idField = 'workspace_id';
    }
}
