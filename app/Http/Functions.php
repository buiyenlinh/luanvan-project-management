<?php 

namespace App\Http;

trait Functions {
  public function success($message, $data = []) {
    return response()->json([
      'status' => 'OK',
      'message' => $message,
      'data' => $data
    ]);
  }

  public function error($error, $data = []) {
    return response()->json([
      'status' => 'ERR',
      'error' => $error,
      'data' => $data
    ]);
  }
}