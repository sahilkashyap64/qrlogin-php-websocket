<?php
namespace App\Http\Controllers\QRLoginWS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Hashids\Hashids;
use Illuminate\Support\Facades\Auth;

class QRLoginWSController extends Controller implements MessageComponentInterface
{
    private $connections = [];

    private $clients;
    private $cache;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        // memory cache
        $this->cache = array();
    }
    public function multicast($msg)
    {
        foreach ($this->clients as $client) $client->send($msg);
    }

    public function send_to($to, $msg)
    {
        if (array_key_exists($to, $this->clientids)) $this->clientids[$to]->send($msg);
    }
    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        $this
            ->clients
            ->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        unset($this->cache[$conn->resourceId]);
        // echo "\n";
        // echo "onClose";
        // print_r($from);
        // print_r(array_keys($this->cache));
        // echo "count:";
        // print_r(count(array_keys($this->cache)));
        $this
            ->clients
            ->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        $this
            ->clients
            ->detach($conn);
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage() }\n";
        $conn->close();
    }

    /**
     * Triggered when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $conn The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n", $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        $obj = json_decode($msg);
        // print_r($obj);
        // echo "\n";
        // echo "onMessage";
        // print_r($from);
        // print_r(array_keys($this->cache));
        // echo "count:";
        // print_r(count(array_keys($this->cache)));
        $type = $obj->type;
        if ($type == 'client')
        {
            switch ($obj->step)
            {
                case 0:
                    // echo "\n inside client,step0 \n";
                    $token = $obj->token;
                    $theuuid = $this->UnHashUserID($token);
                    // echo "theuuid".$theuuid;
                    // echo "\n";
                    // print_r($this->cache);
                    //todo add jwt with 2minutes of token
                    $tokenexist = array_key_exists($theuuid, $this->cache);
                    // $tokenexist= isset($this->cache[$theuuid]);
                    if ($tokenexist)
                    {
                        echo "\n token exist ya \n";
                        $ee = $this->cache[$theuuid];
                        // print_r($ee);
                        if ($ee['status'] == '0')
                        {
                            $this->cache[$theuuid]['status'] = 1;
                            $this->cache[$theuuid] += ['child' => $from];
                            $myArray2[] = (object)['step' => 1];
                            $Scan = new \SplObjectStorage();
                            $Scan->code = 0;
                            $Scan->data = $myArray2[0];
                            $Scan->msg = "Scan code successfully";
                            $this->cache[$theuuid]['parent']->send(json_encode($Scan));

                            $ready2 = new \SplObjectStorage();
                            $ready2->code = 0;
                            $ready2->data = $myArray2[0];
                            $ready2->msg = "Ready";
                            $from->send(json_encode($ready2));
                            // client.send(JSON.stringify({ code: 0, data: { step: 1 }, msg: "Ready" }));
                            
                        };
                        // = [ 'status'=> 0, 'parent'=> 'client' ];
                        // $itemCache=$this->cache[$theuuid];
                        // echo "itemCached".$itemCache;
                        
                    }
                    else
                    {

                        echo "token doesn't exsit";
                        // cache.set(decoded.uuid, -1);
                        
                    }
                break;
                case 1:
                    $myArray3[] = (object)['step' => 2];
                    $myArray4[] = (object)['step' => 2, 'username' => $obj->username];
                    foreach ($this->cache as $v)
                    {
                        if ($v['child'] == $from)
                        {
                            // $token updateSessionToken;
                            $ready3 = new \SplObjectStorage();
                            $ready3->code = 0;
                            $ready3->data = $myArray4[0];
                            $ready3->msg = "Already logged in";
                            if (array_key_exists("parent", $v))
                            {
                            }
                            $v['parent']->send(json_encode($ready3));
                        }
                    }

                    $ready = new \SplObjectStorage();
                    $ready->code = 0;
                    $ready->data = $myArray3[0];
                    $ready->msg = "Login successful";
                    $from->send(json_encode($ready));
            }
        }
        else if ($type == 'server')
        {
            // echo "hello inside server";
            //to get the QR logo
            switch ($obj->step)
            {
                case 0:
                    $uuid = $from->resourceId; //Str::random(30);
                    echo $uuid;
                    $token = $this->HashUserID($uuid);
                    // echo $token;
                    $this->cache[$uuid] = ['status' => 0, 'parent' => $from];
                    $url = env('APP_URL'); // Get the current url
                    // dd($url);
                    $http = $url . '?t=' . $token; // Verify the url method of scanning code
                    $myArray[] = (object)['step' => 0, 'url' => $http];
                    $ready = new \SplObjectStorage();
                    $ready->code = 0;
                    $ready->data = $myArray[0];
                    $ready->msg = "Ready";
                    $from->send(json_encode($ready));

                break;

            }
        }
    }

    public function qrscanner()
    {
        $hashedid = $this->HashUserID(Auth::user()
            ->id);
        return view('qrlogin.scanqr', compact('hashedid'));
    }


  public function loginWS(Request $request){
    $key=$request['key'];
    if (empty($key)){

    $return = array ('status'=>2,'msg'=>'key not provided' );
    return response()->json($return, 200);
    }

    $userid=$this->UnHashUserID($key);
    try {
      $user = Auth::loginUsingId($userid, true);
      $return = array ('status'=>1,'msg'=>'success','jwt'=>1,'user'=>$user );
      return response()->json($return, 200);
    } catch (Exception $exception) {
        
      return response()->json([
        'status'=>2,
          'success' => false,
          'message' => 'Some Error occured',
          'error'=>$exception->getMessage(),
          'response_code'=>200,
          
      ], 200);
  }
    


  }

    private function HashUserID($id)
    {
        $hashids = new Hashids('qrhash', 10);

        $hasid = $hashids->encode($id);
        return $hasid;
    }
    private function UnHashUserID($hasid)
    {
        $hashids = new Hashids('qrhash', 10);
        if (!$hashids->decode($hasid))
        {
            return false;
        }
        else
        {
            return $hashids->decode($hasid) ['0'];
        }

    }

}

