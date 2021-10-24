# How to run the project
- rename .env.example to .env
- add db connection in .env
```sh
composer install
php artisan migrate 
php artisan serve
```
- we got to start the websocket server too,it willl be on 8090 port
```sh
php artisan websocket:init
```
### Vist the link
- register user and login
- open QR scanner using http://baseurl/qrscanner

### Open incognito tab in browser
- open http://baseurl/qrstest
click on 'login with qr' button
qr code comes up save the qr as image.


- In your QR scanner tab
import the QR image(for localhost only), it will be scanned.
OR use the camera to scan(probably will work on prod)




# Show QR Login
### Display QR code 
#### Ping Websocket connection
- Show QR code in frontend using the msg in websocket
1. onconnect send type: "server",step 0,will receive a url with token,make QR code of the url
2. look for step 2 which means user's hash id recieved

#### Ping Http for login
- Send ajax request with the hashed id
3. send it and on success login, redirect to dashboard

## Routes

### 1.QR test page: Ping websocket from js
```sh
method: "Websocket"
url: "ws://localhost:8090"
```
on success response
```js
ws.onopen = function() {
    ws.send(JSON.stringify({
        type: "server",
        code: 0,
        step: 0
    }));

};
ws.onmessage = function(evt) {
    const data = JSON.parse(event.data);
    //console.log("datafromservver",data);
    const step = data.data && data.data.step;
    if (step === 0) {
        //Generate QR Code and show to user.
        $("#qrcode").qrcode({
            "width": 100,
            "height": 100,
            "text": data.data.url
        });
        console.log("QR code generated successfully");

    } else if (step === 2) {
        const {
            username,
            token
        } = data.data;
        //localStorage.setItem(TOKEN_KEY, token);

        $("#qrcode").html("");
        ws.close();
        //alert(username);
        is_loginfun(username);
    }



};
```
### 2. Http Login : is_loginfun(username);
```sh
var key = username;
method: "POST"
url: "web/loginws",
data:{key:key}
```
on success response
```js
success: function(data) {
    if (data.status == 1) {
        var uid = data.jwt;
        var user = data.user;
        console.log("user", user);

        console.log("login successfull", uid);

        alert("login successfull", uid);
        window.location.href = '/';

    } else if (data.status == 2) {
        alert(data.msg);
    }
}
```

# QR scanner App
## Scanner to be accessed by Authenticated User
### Scan QR code 
#### Ping Websocket connection
- Scan QR code in frontend using the msg in websocket
1. onconnect send type: "client", step 0, with token in decoded qr image 
2. look for step 1 and send user's hash id to token

```sh
method: "Websocket"
url: "ws://localhost:8090"
```
on success response
```js
ws.onopen = function() {
    console.log("on WS open we sent the token to server");
    let params = (new URL(url)).searchParams;
    let urltoken = params.get('t');
    ws.send(JSON.stringify({
        type: "client",
        step: 0,
        token: urltoken
    }));

};
ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log(" client body", data);
    const step = data.data && data.data.step;
    if (step === 0) {
        console.log("step", step);
    } else if (step === 1) {
        ws.send(JSON.stringify({
            type: "client",
            step: 1,
            username: '{{$hashedid}}'
        }));
    }

}
```

- QR test page in incognito mode
![QR test](picofWS/1.png?raw=true "QR test page")
- When QR scanned
![When QR scanned successfully](picofWS/2.png?raw=true "When QR scanned successfully")
- Things happening in the websocket
![WS console](picofWS/3.png?raw=true "WS console")
