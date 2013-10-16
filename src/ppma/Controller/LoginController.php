<?php


namespace ppma\Controller;


use PHPassLib\Hash\BCrypt;
use ppma\Config;
use ppma\Controller;
use ppma\Entity\User;
use ppma\Service\Database\Exception\RecordNotFoundException;
use ppma\Service\Database\Spot\UserServiceImpl;
use ppma\Service\Response\HtmlService;
use ppma\Service\Response\JsonServiceImpl;
use ppma\Service\ResponseService;
use ppma\Service\User\SessionServiceImpl;
use ppma\Service\View\PhpServiceImpl;

class LoginController extends ControllerImpl
{

    /**
     * @var HtmlService
     */
    protected $html;

    /**
     * @var ResponseService
     */
    protected $json;

    /**
     * @var UserServiceImpl
     */
    protected $userEntityService;

    /**
     * @var SessionServiceImpl
     */
    protected $userService;

    /**
     * @return array
     */
    public function services()
    {
        return [
            [
                'name' => 'html',
                'id'   => Config::get('services.response.html')
            ],
            [
                'name' => 'json',
                'id'   => Config::get('services.response.json')
            ],
            [
                'name' => 'userEntityService',
                'id'   => Config::get('services.database.user')
            ],
            [
                'name' => 'userService',
                'id'   => Config::get('services.user')
            ],
        ];
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->html->render('login');
    }

    /**
     * @return string
     */
    public function post()
    {
        // prepare response
        $response = [
            'error'   => true,
            'message' => ''
        ];

        // get username and password
        $username = params('username');
        $password = params('password');

        // check if username and password set
        if (is_null($username) || is_null($password))
        {
            $response['message'] = 'Username and password cannot be blank.';
            return $this->json->send($response);
        }

        // retrieve user
        try {
            $user = $this->userEntityService->getByUsername($username);
        } catch (RecordNotFoundException $e) {
            $response['message'] = 'Your login details are invalid.';
            return $this->json->send($response);
        }

        // check password
        if (!BCrypt::verify(md5($password), $user->getPassword()))
        {
            $response['message'] = 'Your login details are invalid.';
            return $this->json->send($response);
        }

        // sign user in
        $this->userService->login($user);

        // all fine
        $response['error']     = false;
        $response['forwardTo'] = site() . config('dispatch.router') . '/app';
        return $this->json->send($response);
    }

}