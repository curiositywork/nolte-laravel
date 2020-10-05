<?php

namespace App\Http\Controllers;

use App\Feedback;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;

class FeedbackController extends Controller
{
    private $feedback;

    public function __construct()
    {
        $this->feedback = new Feedback;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $feedback = $this->feedback->find($id);
        return response()->json([
                'success' => TRUE,
                'data' => $feedback->getVulnerabilities(),
            ], IlluminateResponse::HTTP_OK);
    }

    /**
     * Archive the specified feedback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function archive($id)
    {
        $feedback = $this->feedback->find($id);
        if ($feedback->status != 'archived')
        {
            $feedback->status = 'archived';
            $feedback->save();
        }
        
        return response()->json([
                'success' => TRUE,
                'data' => $feedback,
            ], IlluminateResponse::HTTP_OK);
    }
    
    /**
     * Unarchive the specified feedback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function unarchive($id)
    {
        $feedback = $this->feedback->find($id);
        if ($feedback->status == 'archived')
        {
            $feedback->status = 'pending';
            $feedback->save();
        }
        
        return response()->json([
                'success' => TRUE,
                'data' => $feedback,
            ], IlluminateResponse::HTTP_OK);
    }
}
