<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Functions;
use App\Http\Resources\LabelResource;
use App\Model\Label;
use App\Model\Task;
use Illuminate\Support\Facades\Storage;

class LabelController extends Controller
{
    use Functions;
    /**
     * Lấy danh sách nhãn theo tìm kiếm
     */
    public function searchLabel(Request $request) {
        $keyword = $request->keyword;
        $label = array();
        if (!$keyword) {
            if ($request->callfirst)
                $labels = Label::where('active', 1)->get();
            else 
                return $this->success('Danh sách tìm kiếm nhãn', []);
        } else 
            $labels = Label::where('name', 'LIKE', '%' . $keyword . '%')->where('active', 1)->get();
        

        return $this->success('Danh sách tìm kiếm nhãn', $labels);
    }

    /**
     * Lấy danh sách
     */
    public function getList(Request $request) {
        $list = Label::select('*');
        if ($request->keyword) {
            $list->whereRaw('name LIKE "%' . $request->keyword . '%"');
        }

        $list = $list->orderBy('id','desc')->paginate(5);
        $data = LabelResource::collection($list)->response()->getData();

        return $this->success('Danh sách nhãn' . $request->keyword, $data);
    }

    /**
     * Thêm nhãn
     */
    public function add(Request $request) {
        $name = $request->name;
        $describe = $request->describe;
        $active = $request->active;

        if (!$name) return $this->error('Tên nhãn là bắt buộc');
        if ($active != 1 && $active != 0) return $this->error('Trạng thái nhãn không hợp lệ');

        $check_name = Label::where('name', $name)->count();
        if ($check_name > 0) return $this->error('Tên nhãn đã được sử dụng');

        if (!$describe) $describe = '';

        $file = '';
        if ($request->file('file')) {
            $file = $request->file('file')->store('public/files');
            $file = str_replace('public/', '', $file);
        }

        Label::create([
            'name' => $name,
            'describe' => $describe,
            'file' => $file,
            'active' => $active
        ]);

        return $this->success('Thêm nhãn thành công', []);
    }

    /**
     * Cập nhật nhãn
     */
    public function update(Request $request, $id) {
        $name = $request->name;
        $describe = $request->describe;
        $active = $request->active;

        $label = Label::find($id);
        if (!$label) return $this->error('Nhãn không tồn tại');
        if (!$name) return $this->error('Tên nhãn là bắt buộc');
        if ($active == null) return $this->error('Trạng thái nhãn là bắt buộc');
        if ($active != 1 && $active != 0) return $this->error('Trạng thái nhãn không hợp lệ');

        $check_name = Label::where('name', $name)->where('id', '!=', $id)->count();
        if ($check_name > 0) return $this->error('Tên nhãn đã được sử dụng');

        if (!$describe) $describe = '';

        $file = $label->file;
        if ($request->file('file')) {
            if (!empty($file)) {
                Storage::disk('public')->delete($file);
            }

            $file = $request->file('file')->store('public/files');
            $file = str_replace('public/', '', $file);
        }

        $label->update([
            'name' => $name,
            'describe' => $describe,
            'file' => $file,
            'active' => $active
        ]);

        return $this->success('Cập nhật nhãn thành công', []);
    }

    /**
     * Xóa nhãn
     */

    public function delete(Request $request, $id) {
        $label = Label::find($id);
        if (!$label) return $this->error('Nhãn không tồn tại');

        $check_task = Task::where('label_id', $id)->count();
        if ($check_task > 0) return $this->error('Nhãn đã được sử dụng cho công việc không xóa được');

        if (!empty($label->file)) {
            Storage::disk('public')->delete($label->file);
        }

        $label->delete();

        return $this->success('Xóa nhãn thành công', []);
    }
}
