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
        $feedback = Feedback::find($id)->load('vulnerabilities');
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
        $feedback = Feedback::find($id);
        if ($feedback->status != 'archive')
        {
            $feedback->status = 'archive';
            $feedback->save();
        }
        
        return response()->json(
            [
                'success' => TRUE,
                'data' => $feedback,
            ], IlluminateResponse::HTTP_OK);
    }

    public function unarchive($id)
    {
        $feedback = Feedback::find($id);
        if ($feedback->status == 'archive')
        {
            $feedback->status = 'pending';
            $feedback->save();
        }
        
        return response()->json(
            [
                'success' => TRUE,
                'data' => $feedback,
            ], IlluminateResponse::HTTP_OK);
    }
}
