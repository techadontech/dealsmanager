<?php
/**
 * @uses run auth methods and endpoints
 * @return mixed, validation and route endpoint handling
 */

    namespace DealsManager\Controllers;
    use DealsManager\Controllers\Controller;
    use DealsManager\Models\User;
    use DealsManager\Controllers\JWTController;
    use Carbon\Carbon;

    require __DIR__."/../../vendor/swiftmailer/swiftmailer/lib/classes/Swift/Message.php";

    class AuthController extends Controller{

        //validate email and retun json string
        public function checkEmailValidity($request, $response){

            $email = filter_var($request->getParsedBodyParam("email"), FILTER_SANITIZE_EMAIL);

            if(empty($email)){

                return $response->withJson(["notice"=>"Error","message"=>"Sorry the email address is empty", "result"=>"false"],200);
            }

            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){

                return $response->withJson(["notice"=>"Error","message"=>"Sorry the email address is invalid, please provide a valid email", "result"=>"false"],200);

            }

            return $response->withJson(["notice"=>"Success", "message"=>"Great your email is valid", "result"=>"true", "email"=>$email], 200);

        }

        //start the run user methods
        public function login($request, $response, $args){

            $email = base64_decode($args['email']);

            $token = $args['token'];

            $user = User::where('email', $email)->get()->take(1)->first();

            //as long as token is not old
            if(User::where('email', $email)->get()->take(1)->count()){

                $now = Carbon::now();
                $tokendate = new Carbon($user->tokendate);

                if($tokendate->diffInHours($now) < 25){
                
                    return $this->view->render($response, 'login.twig', ['email'=>$email, 'token'=>$token]);
    
               }else{

                    $this->flash->addMessage('error', 'Sorry Your Token has expired Please sign in again');
                   return $response->withRedirect($this->router->pathFor('signin'));
    
               }

            }else{

                $this->flash->addMessage('error', 'Your email Address and Key was not found');
                return $response->withRedirect($this->router->pathFor('signin'));

            }

            //var_dump(Carbon::parse($user->tokendate));
        }

        public function postLogin($request, $response){
            //fetch params
            $email = $request->getParsedBodyParam('email');
            $password = $request->getParsedBodyParam('password');
            $token = $request->getParsedBodyParam('token');

            $userIn = User::where('email', $email)->get()->take(1)->first();
            $tokenldate = new Carbon($userIn->tokendate);

            //run the check method for user email check
            if(!$this->checkUser($email)){

                $this->flash->addMessage("error", "Your email account was not found try signing in");
                return $response->withRedirect($this->router->pathFor('signin'));
            }

            if($token == $userIn->tokenvalue && $tokenldate->diffInHours(Carbon::now()) < 25){

                if(!password_verify($password, $userIn->password)){

                    $message = ['notice'=>'error', 'message'=>'Password is not correct', 'result'=>'false'];

                }else{
                    
                    //return the cookie and token header for use
                    return $this->setupUserNow($email, $userIn->id, $userIn->name, $request, $response);
                  
                }

            }else{

                $message = ['notice'=>'error', 'message'=>'Sorry its Either  Your Token has expired or cant be found', 'result'=>'false'];
            }

            return $response->withJson($message,200);
            
        }

        public function checkUser($email){

            $userAvail = User::where('email', $email)->get()->take(1)->count();

            if($userAvail != 0){
                return true;
            }

            return false;
        }

        public function setupUserNow($email, $id, $name, $request, $response){

            $cookieValJWT = $this->JWTAUTH->authenticate($id, $name, $email);
            setcookie('umid', $cookieValJWT, time()+86500 * 30, 'http://localhost/dealsmanager/', False, False, True);
            
            //forwarding request
            $this->flash->addMessage('success','Great stuff you\'re In');
            return $response->withHeader('X-Access-Token', $cookieValJWT);

        }

        //control logout
        public function logOut($request, $response){

            //check existence of cookie nd cut it out
            if(isset($_COOKIE['umid'])){

                unset($_COOKIE['umid']);
                setcookie('umid', '', time() - 3600, 'http://localhost/dealsmanager/');

                $this->flash->addMessage('success', 'You have sucessfully Logged out');
                return $response->withRedirect($this->router->pathFor('signin'));

            }

            $this->flash->addMessage('error', 'You have to be logged in to log out');
            return $response->withRedirect($this->router->pathFor('signin')); 
        }

    }




?>