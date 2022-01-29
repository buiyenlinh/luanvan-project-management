<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;

use App\Model\Label;

class LabelController extends Controller
{
    use Functions;
    /**
     * Lấy danh sách nhãn
     */
    public function searchLabel(Request $request) {
        $keyword = $request->keyword;
        if (!$keyword) {
            return $this->success('Danh sách tìm kiếm nhãn', []);
        }

        $labels = Label::where('name', 'LIKE', '%' . $keyword . '%')->get();
        return $this->success('Danh sách tìm kiếm nhãn', $labels);
    }
}
