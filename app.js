var recognizing = false;
var voices = window.speechSynthesis.getVoices();
var wait = true;

var onHearCallback = null;
var ASR = new webkitSpeechRecognition();

// Check if the checkbox change
var changeCheckboxValue = function(cb){
    wait = !wait;
    console.log(wait);
};

var dialogueStatus = 'start';

var reset = function(){
    onHearCallback = undefined;
    dialogueStatus = 'start';
    ASR.abort();
    window.speechSynthesis.cancel();
    speak("Bye!");
    $("#suggestionBox").addClass('hidden');
    $("#suggestionMessage").removeClass('hidden');
    updateComputerLog("");
}

var hear = function(callback){
    ASR.interimResults = false;
    ASR.lang='en-US';
    ASR.maxAlternatives=10;
    ASR.continuous = false;
    console.log("set callback", callback);
    onHearCallback = callback;
    var attempt = 0;

    // Start
    ASR.start();
    
    // on start
    ASR.onstart = function() {
        recognizing = true;
        console.log('started recognition');
    };


    ASR.onend = function() {
        recognizing = false;
        console.log('stopped recognition');
    };

    ASR.onerror = function(event) {
        console.log("error");
        console.log(event);
    };


    ASR.onresult = function(event) {
        window.speechSynthesis.cancel();
        console.log(event);
        for (var i = 0; i < event.results.length; ++i) {
            if (event.results[i].isFinal) {
                for (var j = 0; j < event.results[i].length; ++j) {
                    transcript=event.results[i][j].transcript;
                    confidence=event.results[i][j].confidence;
                    console.log('result:'+transcript+' conf:'+confidence);
                }
                best_transcript=event.results[0][0].transcript;
                best_confidence=event.results[0][0].confidence;
                updateMyLog(best_transcript);
                if (best_transcript == "stop" || best_transcript == "reset") return reset();
                callback(best_transcript, best_confidence);
            }
        }
    };

};

var speak = function(textToSpeak, waitForCallback, callback){
    var TTS = new SpeechSynthesisUtterance();
    voices = window.speechSynthesis.getVoices();
    TTS.lang = 'en-GB';
    TTS.pitch = 1; //0 to 2
    TTS.voice = voices[2]; //Not all supported
    TTS.voiceURI = 'native';
    TTS.volume = 1; // 0 to 1
    TTS.rate = 1; // 0.1 to 10
    TTS.text = textToSpeak;
    updateComputerLog(textToSpeak);

    if (!waitForCallback && callback) callback();

    window.speechSynthesis.speak(TTS);
    TTS.onend = function(event) {
        console.log('Utterance "', textToSpeak, '"', 'has finished being spoken.');
        if (callback && waitForCallback) callback();
    }
};

var updateMyLog = function(message){
        if (!message) return;
        $("#questionBox").val(message);
}

var updateComputerLog = function(message){
        if (!message) return;
        $("#computerQuestion").text(message);
}

var addSuggestionToList = function(message, clickable){
    var node = '<button onclick="clickOnElement(this)" class="list-group-item">' + message + '</button>';
    if (!clickable) node = '<button class="list-group-item">' + message + '</button>';
    $("ul").append(node);
};

var clickOnElement = function(element){
    var value = element.innerHTML;
    $("#questionBox").val(value);
    submitText();
}

var clearSuggestionList = function(){
    $("ul").empty();
};


function handleDialogue(json_data) {
    var results = json_data.results;
    var message = json_data.message;
    
    console.log("new incoming message", message);
    $("#suggestionBox").removeClass('hidden');


    // Clear the suggestions list
    clearSuggestionList();
    dialogueStatus = message;

    switch (message) {
        case 'ask_intent':
            askMultipleChoice(results, function(intent, sure){
                var content = {'intent': intent, 'sure': sure};
                var status = message;
                sendMessage(content, status, handleDialogue);
            });
            break;
        // case 'ask_target':
        //     ask(results, function(tag, sure, target){
        //         var content = {'tag': tag, 'sure': sure, 'target': target};
        //         var status = message;
        //         sendMessage(content, status, handleDialogue);
        //     });
        //     break;
        case 'confirm_target':
            askTarget(results, function(tag, sure, target){
                var content = {'tag': tag, 'sure': sure, 'target': target};
                var status = message;
                sendMessage(content, status, handleDialogue);
            });
            break;
        default:
            var response = results['response'];
            var originalQuestion = results['question'];
            var message = "";

            $("#suggestionBox").addClass('hidden');
            $("#suggestionMessage").removeClass('hidden');


            if (!response){
                message = "Sorry, cannot find an answer in my database.";
                message += "I will ask google for you!";
                console.log(message);
                speak(message, true, function(){
                    window.open('http://google.com/search?q='+originalQuestion);
                });
            } else {
                message = "The answer is: " + response;
                console.log(message);
                speak(message);
                dialogueStatus = 'start';
            }

            break;
    }
}

// From button
function onStartASR(event) {
    var message = "Hi, what is your question?";
    speak(message, wait, function(){
        hear(function(transcript, confidence){
            $("#questionBox").val(transcript);
            var content = {'asrResult': transcript, 'asrConfidence': confidence};
            speak("ok, give me a second", true, function(){
                sendMessage(content, "start", handleDialogue);
            });
         });
        console.log('onStartASR Pressed to start recognition');
    });
}

// From button
function submitText(event){
    window.speechSynthesis.cancel();
    ASR.abort();
    console.log(onHearCallback);
    if (onHearCallback && dialogueStatus != 'start') return onHearCallback($("#questionBox").val(), 1);
    var content = {'asrResult': $("#questionBox").val(), 'asrConfidence': 1};
    var message = "start";
	sendMessage(content, message, handleDialogue);
}

function containsAny(str, substrings) {
    for (var i = 0; i != substrings.length; i++) {
       var substring = substrings[i];
       if (str.indexOf(substring) != - 1) {
         return substring;
       }
    }
    return null; 
}

function askMultipleChoice(elements, callback){
    console.log(elements);
    var message = "Did you ask for ";
    var intents = [];
    elements.forEach(function(ele) {
        var intent = ele[0];
        intents.push(intent);
        addSuggestionToList(intent, true);
        message += intent + ", or ";
    });
    addSuggestionToList("Say something else", false);
    message = message.substr(0, message.length - 5);
    message += "?";
    speak(message, wait, function(){
        hear(function(transcript, confidence){
            var intentInString = containsAny(transcript, intents);
            if (intentInString) {
                //In the array!
                message = "Ok, I got it. I'm looking for " + intentInString;
                speak(message, true, function(){
                    callback(intentInString, true);
                });
            } else {
                //Not in the array
                callback(transcript, false);
            }
        });
    });
};

function ask(element, callback){
    console.log(element);
    var message = "I have never hear about " + element + " . What is it?";
    speak(message, wait, function(){
        hear(function(transcript, confidence){
            callback(transcript, false, element);
        });
    });
};

function askConfirmation(subject, obj, callback){
    var message = 'Ok, just little more clarification, is "' + subject + '" a "' + obj +  '"? Yes or no?';
    clearSuggestionList();
    addSuggestionToList("yes", true);
    addSuggestionToList("no", true);
    speak(message, wait, function(){
        hear(function(transcript, confidence){
            transcript = transcript.toLowerCase();
            if (transcript.indexOf('no') > -1) {
                // no
                clearSuggestionList();
                addSuggestionToList("Say what it is (movie, actor, ...)", false);
                var message = "Ok, I was wrong. What is it?";
                speak(message, wait, function(){
                    hear(function(transcript, confidence){
                        // send back the transcript   
                        callback(transcript, false, subject);     
                    });
                });
            } else {
                // yes
                // send back the tag
                callback(obj, true, subject);
            }
        });
    });
};

function askTarget(element, callback){
    console.log(element);
    var tags = element['tag'].split(".");
    var tag = tags[0];
    var text = element['text'];
    var message = 'Wait, are you asking for "' + text + '", right?';
    clearSuggestionList();
    addSuggestionToList("yes", true);
    addSuggestionToList("no", true);
    speak(message, wait, function(){
        hear(function(transcript, confidence){
            transcript = transcript.toLowerCase();
            clearSuggestionList();
            if (transcript.indexOf('no') > -1) {
                message = "Ok, what then?";
                clearSuggestionList();
                addSuggestionToList("Say the target of your question (ex. title of the movie)", false);
                speak(message, wait, function(){
                    hear(function(transcript, confidence){
                        // send back the transcript   
                        return askConfirmation(transcript, tag, callback);    
                    });
                });
            } else {
                message = "Perfect.";
                speak(message, wait, function(){
                        // send back the transcript   
                        return askConfirmation(text, tag, callback);    
                });
            }
        });
    });
};

function sendMessage(messageContent, messageStatus, callback){
    console.log("sending a message of type", messageStatus, "with content", messageContent);
    var base_url = "controller1.php";
    var params = { content: messageContent, status: messageStatus};
    var decodeParams = $.param( params );
    var url = base_url + "?" + decodeParams;
    $.getJSON(url + '&callback=?', callback);
}