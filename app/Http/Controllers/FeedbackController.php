<?php

namespace App\Http\Controllers;

use App\Feedback;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;

class FeedbackController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $feedback = Feedback::findOrFail($id)->with('vulnerabilities')->get();
        return response()->json(
            [
                'success' => TRUE,
                'data' => $feedback,
            ], IlluminateResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function archive($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->status = 'archive';
        $feedback->save();

        return response()->json(
            [
                'success' => TRUE,
                'data' => $feedback,
            ], IlluminateResponse::HTTP_OK);
    }
}
