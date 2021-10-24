
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ asset('js/qr/html5-qrcode.min.js') }}" ></script>
        <!-- Styles -->
 
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('QR Scanner|| Web socket') }}
        </h2>
    </x-slot>
    <div class="container mx-auto">
    
<p>passcode: {{$hashedid}}</p>
<p>Name: {{Auth::user()->name}}</p>
<p>Email: {{Auth::user()->email}}</p>
						 <div id="qr-reader-results"></div>	
              <img class="qrcode-img" src="" />
              <div id="qr-reader" ></div>
   
     <p id="login_mobile_scan_qrcode"></p>
     <p id="qrcodedoLogin"></p>
  
</div>
    </x-app-layout>
<script>
$(document).ready(function() {
  function qrcodedoLogin(param){
      var url = param;
      console.log("qrcodedoLogin called",url);
    $.ajax({
        type: "POST" ,
        dataType: "json" ,
        url: url ,
        data:{
            //key:key
        },
        success:function(data) {
              if (data.status==1 ){
                  var qrcodeloginurl = data.msg;
            //scan successfull url recieved

                  $('#qrcodedoLogin').text("QR Loggin successfully");
               // console.log("qrcodeloginurl",qrcodeloginurl);
//qrcodedoLogin(qrcodeloginurl);

            } else  if (data.status==2 ){
//couldn't do login
               // alert(data.msg);

                  $('#qrcodedoLogin').text(data.msg);
            }
        }
    });
}
function login_mobile_scan_qrcode(param){
      var url = param;
	  if ("WebSocket" in window) {
		  var base = window.location.hostname;
		var ws = new WebSocket('ws://'+base+':8090');
		//var ws = new WebSocket('wss://'+base+':8090');
                ws.onopen = function() {
            console.log("on WS open we sent the token to server");
			let params = (new URL(url)).searchParams;
			let urltoken = params.get('t'); 
            ws.send(JSON.stringify({ type: "client", step: 0, token: urltoken }));

                };
				ws.onmessage = function(event) {
					const data = JSON.parse(event.data);
					console.log(" client body",data);
					 const step = data.data && data.data.step;
                           if (step === 0) {
console.log("step",step);
						   }else if (step === 1) {
 ws.send(JSON.stringify({ type: "client", step: 1, username:'{{$hashedid}}' }));
						   }

				}
				ws.onclose = function() {
                    console.log("Connection is closed...");
                };
	  } else {
                alert("WebSocket NOT supported by your Browser!");
            }
     // console.log("login_mobile_scan_qrcode called",url);
    
}
    
    function docReady(fn) {
        // see if DOM is already available
        if (document.readyState === "complete"
            || document.readyState === "interactive") {
            // call on next available tick
            setTimeout(fn, 1);
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

    docReady(function () {
        var resultContainer = document.getElementById('qr-reader-results');
        var lastResult, countResults = 0;
        function onScanSuccess(decodedText, decodedResult) {
            if (decodedText !== lastResult) {
                ++countResults;
                lastResult = decodedText;
                // Handle on success condition with the decoded message.
                console.log(`Scan result ${decodedText}`, decodedResult);
				 resultContainer.innerHTML += `<div>[${countResults}] - ${decodedText}</div>`;
            
           
                login_mobile_scan_qrcode(decodedText);
				 // Optional: To close the QR code scannign after the result is found
           // html5QrcodeScanner.clear();
            }
        }

        var html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", { fps: 10, qrbox: 250 });
        html5QrcodeScanner.render(onScanSuccess);
    });

});
</script>
