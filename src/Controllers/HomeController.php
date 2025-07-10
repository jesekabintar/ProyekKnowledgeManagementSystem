<?php
namespace User\KnowledgeManagement\Controllers;

class HomeController {
    public function index($request, $response) {
        return $response->write('Halo dari controller!');
    }
}
