<?php

    ini_set('memory_limit', '1024M');
    session_start();

    // for SLU processing
    require 'FstClassifier.php';
    require 'FstSlu.php';
    require 'SluResults.php';

    // for DB
    require 'Slu2DB.php';
    require 'QueryDB.php';

    // configure paths
    $classifier = 'models/MAP.fst';
    $cilex      = 'models/classifier.lex';
    $colex      = 'models/classifier.lex';
//    $lm         = 'models/slu.lm';
//    $wfst       = 'models/wfst.fst';
    $lm         = 'models/myslu.lm';
    $wfst       = 'models/mywfst.fst';
//    $sluilex    = 'models/slu.lex';
//    $sluolex    = 'models/slu.lex';
    $sluilex    = 'models/mylexicon.lex';
    $sluolex    = 'models/mylexicon.lex';
    $unk        = '<unk>';

    $UC  = new FstClassifier($classifier, $cilex, $colex, $unk);
    $SLU = new FstSlu($wfst, $lm, $sluilex, $sluolex, $unk);
    $SR  = new SluResults();
    $QC  = new Slu2DB();
    $DB  = new QueryDB();

    // Constants
    $th_accept = 0.87;
    $th_reject = 0.75;
    $intent_accept = 0.93;

    $error = false;
    $error_topic;
    $error_message;

    // Define a function to debug in peace
    function console_log( $data, $name ){
        error_log(print_r(json_encode($name), TRUE)); 
        error_log(print_r(json_encode($data), TRUE));
    }

    function write_conf( $confidence, $type){
        $file_path = "positives.txt";
        if (!$type){
            $file_path = "negatives.txt";
        }
        $content = $confidence;
        $myfile = file_put_contents($file_path, $content.PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    function sendMessage($arr, $message) {
        $response = array('results'=>$arr, 'message'=>$message);
        $json = json_encode($response);
        $callback = $_GET['callback'];
        echo $callback.'('. $json . ')';
        exit();
    }

    function checkSLU($slu_result, $slu_confidence){
        global $th_accept;
        global $th_reject;

        if ($slu_confidence >= $th_accept){
            console_log($slu_result, 'checkSLU:goodconfidence');
            return $slu_result;
        } else {
            // Ask for confermation;
            console_log($slu_result, 'checkSLU:lowconfidence');
            foreach($slu_result as $concept=>$val){
                $content = array('text' => $val, 'tag' => $concept);
                sendMessage($content, 'confirm_target');
                break; // because I want to access only the first element
            }
        }

        /*
        if ($slu_confidence >= $th_accept){
            console_log($slu_result, 'checkSLU:goodconfidence');
            return $slu_result;
        } else if ($slu_confidence < $th_reject) {
            // I know that you are talking about "The matrix"
            // but I don't know what it is
            console_log($slu_result, 'checkSLU:midconfidene');
            foreach($slu_result as $concept=>$val){
                sendMessage($val, 'ask_target');
                break;
            }
        } else {
            // Ask for confermation;
            console_log($slu_result, 'checkSLU:lowconfidence');
            foreach($slu_result as $concept=>$val){
                $content = array('text' => $val, 'tag' => $concept);
                sendMessage($content, 'confirm_target');
                break; // because I want to access only the first element
            }
        }
        */
    }

    function checkIntent($intent_result, $intent_confidence){
        global $intent_accept;
        if ($intent_confidence < $intent_accept){
            // send a message to the user
            // pick the first n and ask
            $all_uc = array();
            foreach ($intent_result as $res) {
                $all_uc[] = $res;
            }
            console_log($intent_result, 'checkIntent:lowconfidence');
            sendMessage($all_uc, 'ask_intent');
        } else {
            console_log($intent_result, 'checkIntent:goodconfidence');
            return $intent_result;
        }
    }

    function getResultsFromDB($slu_result, $intent){
        global $QC;
        global $DB;

        console_log($slu_result, 'before query slu_results');
        console_log($intent, 'before query intent');
        //------------------------------------------------------------------
        // Convert SLU results to SQL Query
        //------------------------------------------------------------------
        $query = $QC->slu2sql($slu_result, $intent); // build the query
        console_log($query , "Query SQL");
        
        //------------------------------------------------------------------
        // Query DB
        //------------------------------------------------------------------
        $db_results = $DB->query($query);       // run the query
        if ($db_results == null) return null;

        $intent = $QC->db_mapping($intent); // actor -> actors
        console_log($db_results, "DB response");


        $response = array();
        foreach ($db_results as $res) {
            if (!in_array($res[$intent], $response)){
                    $response[] = $res[$intent];
            }
        }

        console_log($response, "DB first result");

        return $response;
    }

    function predict($userMessage){
        global $UC;
        $userMessage = utf8_encode($userMessage);
        //$possibilities = ["actor", "movie", "director", "character"];
        $possibilities = ["actor", "movie", "director", "character", "duration", "genres",
                          "title", "year"];
        $result = "";
        foreach ($possibilities as $possibleIntent) {
            if (strpos($userMessage, $possibleIntent) !== false){
                console_log("find a match with $possibleIntent", "maybe");
                $result = $possibleIntent;
                break;
            }
        }

        if (empty($result)) {
            $result = $UC->predict($userMessage, TRUE, 1);
            $result = $result[0][0];
        }

        return $result;
    }

    function start($question, $asr_confidence){
        // Access global variables
        global $UC;
        global $SLU;
        global $SR;
        global $QC;
        global $DB;

        global $th_accept;
        global $th_reject;
        global $intent_accept;

        global $error;
        global $error_topic;
        global $error_message;
        // end declare global variable

        console_log($question, "input_sentence");

        $utterance = $question;
        $utterance = trim(strtolower($utterance));
        $_SESSION["question"] = $utterance;


        // Run SLU
        $slu_out = $SLU->runSlu($utterance, TRUE, 3);

        // Run Utterance classifier
        $uc_out  = $UC->predict($utterance, TRUE, 3);

        //console_log($slu_out, "slu_out");
        //console_log($uc_out, "uc_out");

        // Get the first acceptable tagging
        console_log($asr_confidence, 'asr_confidence');
        $i = 0;
        $slu_tags;
        $slu_conf;
        while ($i < count($slu_out)) {
            $slu_tags = $slu_out[$i][0];
            $slu_conf = $slu_out[$i][1] * $asr_confidence;
            $results = $SR->getConcepts($utterance, $slu_tags);
            if (!empty($results)){
                break;
            } else {
                $i = $i + 1;
            }
        }

        if (empty($results)){
            // Still not find a valid tagging
            $content = array('error' => "no_tagging", 'question' => $_SESSION["question"]);
            sendMessage($content, 'final');
        }

        // Get intent
        $uc_class = $uc_out[0][0];

        // Get intent confidence
        $uc_conf  = $uc_out[0][1];

        console_log($results, "SLU Concepts and Values");
        console_log($slu_conf, "SLU Confidence");

        $_SESSION["slu_results"] = $results;
        $_SESSION["slu_confidence"] = $slu_conf;

        // Check the intent confidence
        $intent = checkIntent($uc_out, $uc_conf);
        $intent = $intent[0][0];
        $_SESSION["intent"] = $intent;

        // Check the target
        $target = checkSLU($results, $slu_conf);

        // Get response from db
        $db_response = getResultsFromDB($target, $intent);

        // Send back a message
        $content = array('response' => $db_response, 'question' => $_SESSION["question"]);
        sendMessage($content, 'final');
    }

    // Entry point - routing
    $status = $_GET['status'];
    switch ($status) {
        case 'ask_intent':
            // retrieve intent information
            $slu_results = $_SESSION["slu_results"];
            $slu_confidence =  $_SESSION["slu_confidence"];
            $content = $_GET["content"];
            $result_intent = $content["intent"];
            $is_intent_sure = $content['sure'];
            if ($is_intent_sure == 'false'){
                // pass the result intent to the classifier
                // pick the best classification
                console_log($result_intent, 'try to predict');
                $result_intent  = predict($result_intent);
                console_log($result_intent, 'prediction result');
            }

            // save intent
            $_SESSION["intent"] = $result_intent;
            
            // Check the target
            $target = checkSLU($slu_results, $slu_confidence);

            // Get response from db
            $db_response = getResultsFromDB($target, $result_intent);

            // Send back a message
            $content = array('response' => $db_response, 'question' => $_SESSION["question"]);
            sendMessage($content, 'final');
            break;
        case 'ask_target':
            // retrieve intent information
            $slu_results = $_SESSION["slu_results"];
            $slu_confidence =  $_SESSION["slu_confidence"];
            $result_intent = $_SESSION["intent"];

            $content = $_GET["content"];
            $result_tag = $content['tag'];
            $result_target = $content['target'];
            $is_tag_sure = $content['sure'];
            console_log($is_tag_sure, "sure?");

            if ($is_tag_sure == "false"){
                console_log($result_tag, 'try to predict');
                $result_tag  = predict($result_tag);
                console_log($result_tag, 'prediction result');
            }

            // Recreate slu_results object from response
            $target = array($result_tag => $result_target);

            // Get response from db
            $db_response = getResultsFromDB($target, $result_intent);

            // Send back a message
            $content = array('response' => $db_response, 'question' => $_SESSION["question"]);
            sendMessage($content, 'final');
            break;
        case 'confirm_target':
            // retrieve intent information
            $slu_results = $_SESSION["slu_results"];
            $slu_confidence =  $_SESSION["slu_confidence"];
            $result_intent = $_SESSION["intent"];

            $content = $_GET["content"];
            $result_tag = $content['tag'];
            $result_target = $content['target'];
            $is_tag_sure = $content['sure'];
            console_log($is_tag_sure, "sure?");
            if ($is_tag_sure == 'false'){
                console_log($result_tag, 'try to predict');
                $result_tag  = predict($result_tag);
                console_log($result_tag, 'prediction result');
            }

            // Recreate slu_results object from response
            $target = array($result_tag => $result_target);

            // Get response from db
            $db_response = getResultsFromDB($target, $result_intent);

            // Send back a message
            $content = array('response' => $db_response, 'question' => $_SESSION["question"]);
            sendMessage($content, 'final');
            break;
        default:
            // start
            $request = $_GET["content"];
            $question = $request['asrResult'];
            $asr_confidence = $request['asrConfidence'];
            start($question, $asr_confidence);
            break;
    }

?>
