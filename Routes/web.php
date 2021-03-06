<?php
    /**
     * @uses define web based routes
     * @return mixed type views and process execution
     */
    //include controllers to be used
     use DealsManager\Controllers\HomeController;
     use DealsManager\Controllers\AuthController;
     use DealsManager\Controllers\AuthViewController;
     use DealsManager\Middlewares\AuthMiddleware;
     use DealsManager\Middlewares\GuestMiddleware;
     use DealsManager\Models\User;
     use Carbon\Carbon;


     
    $app->group('', function(){

        $this->get("/home", AuthViewController::class.":index")->setName('home');
        $this->get("/managedeals", AuthViewController::class.":manageDeals")->setName('managedeals');
        $this->get("/logout", AuthController::class.":logOut")->setName('logout');

    })->add(AuthMiddleware::class);

    $app->group('', function(){

     $this->get("/", HomeController::class.":index")->setName('signin');
     $this->post("/checkemailvalidity", AuthController::class.":checkEmailValidity");

     $this->get("/login/{email}/{token}", AuthController::class.":login");


     $this->post("/login", AuthController::class.":postLogin");

     $this->post("/signinuser", function($request, $response){
        //getting values
        $email = filter_var($request->getParsedBodyParam('email'), FILTER_SANITIZE_EMAIL); 
        $name = substr($email, 0, strpos($email, "@"));

        //setting encrpytion rules and algorithm 
        $emailParam = base64_encode($email);
        $cstrong = TRUE;
        $loginToken = bin2hex(openssl_random_pseudo_bytes(64, $cstrong));
        $url = "http://localhost/dealsmanager/login/".$emailParam."/".$loginToken;

        //password generator
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = substr( str_shuffle( $chars ), 0, 12 );

        //inserting in db
        if(User::where('email', $email)->count() == 0){

            User::create(['name'=>$name,'email'=>$email,'password'=>password_hash($password, PASSWORD_DEFAULT),'tokenvalue'=>$loginToken,'tokendate'=>Carbon::now()->toDateTimeString()]);

        }else{

            User::where('email',$email)->update(['name'=>$name,'email'=>$email,'password'=>password_hash($password, PASSWORD_DEFAULT),'tokenvalue'=>$loginToken,'tokendate'=>Carbon::now()->toDateTimeString()]);
        }
        
        //parse data to html email view
        $datal = array('name'=>$name, 'url'=>$url, 'password'=>$password);   
        $messageBody = $this->view->fetch('Emails/signin.twig', $datal);

        //send email
        $message = Swift_Message::newInstance($name." Sign In Key")->setFrom(['leon@techadon.tech' => 'Leon'])->setTo([$email => $name])
        ->setBody($messageBody)
        ->setContentType('text/html');

        $this->mail->send($message);

        return "true";
     });

    })->add(GuestMiddleware::class);

     


?>