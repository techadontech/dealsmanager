<?php

/**
 * @uses secure execs and views using the defined JWT AUTH
 * @return next response permission
 */

namespace DealsManager\Middlewares;
use Firebase\JWT\JWT;
use DealsManager\Middlewares\Middleware;
use DealsManager\Controllers\AuthController;
use DealsManager\Models\User;

class AuthMiddleware extends Middleware{

    //invokable auth
    public function __invoke($request, $response, $next){

        //check the existence of headers or cookie
        if(!isset($_COOKIE['umid']) || $request->getHeader('X-Access-Token') == ""){

            $this->flash->addMessage('error','Your Token or cookie session was not found');
            return $response->withRedirect($this->router->pathFor('signin'));
        }

        //grab the encoded jwt array
        if(isset($_COOKIE['umid']) && $_COOKIE['umid'] != ""){

            $value = JWT::decode($_COOKIE['umid'], $this->container['settings']['jwt']['secret'], array('HS256'));

            if(!User::where('email',$value->email)->get()->take(1)->first()){

                $this->flash->addMessage('error','Your Token or cookie session link to user\'s account was not found');
                return $response->withRedirect($this->router->pathFor('signin'));

            }
            

        }
        return $next($request, $response);

    }

}


?>