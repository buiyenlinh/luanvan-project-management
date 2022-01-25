<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;
use App\Model\Task;
use App\Model\Project;

class TaskController extends Controller
{
    use Functions;
    /**
     * Danh sách công việc trong một dự án
     */
    public function taskInProject(Request $request) {
        $project_name = $request->project_name;
        if (!$project_name) {
            return $this->error('Vui lòng thử lại');
        }

        $project = Project::where('alias', $project_name)->first();

        $tasks = Task::where('project_id', $project->id)->get();
        return $this->success('Danh sách công việc', $tasks);
    }
}
