<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 20/02/2020
 * Time: 16:35
 */

namespace Plexus;


use Plexus\Exception\HaltException;

trait ControlerAPI {

    /**
     * @return Application
     */
    public function getApplication() {
        return parent::getApplication();
    }

    /**
     * @param $payload
     * @throws HaltException
     */
    protected function success($payload) {
        $response = $this->getApplication()->getResponse();
        $response->setStatusCode(200);
        $response->json(
            [
                "status" => 200,
                "success" => true,
                "payload" => $payload
            ]
        );
        throw new HaltException(200);
    }

    /**
     * @param $code
     * @param $message
     * @throws HaltException
     */
    protected function error($code, $message) {
        $response = $this->getApplication()->getResponse();
        $response->setStatusCode($this->getApplication()->httpCodeForHaltCode($code));
        $response->json(
            [
                "status" => $code,
                "success" => false,
                'message' => $message
            ]
        );
        throw new HaltException($code);
    }
}