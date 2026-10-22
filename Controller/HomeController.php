<?php

namespace Controller;

require_once __DIR__ . '/../Models/sportModel.php';

use Models\SportModel;

class HomeController
{
    private SportModel $model;

    public function __construct()
    {
        $this->model = new SportModel();
    }

    public function index()
    {
        $model = $this->model;
        $categories = $model->all();

        require __DIR__ . '/../Vue/home.php';
    }

    public function sport(string $urlName)
    {
        $name = $this->model->findByUrlName($urlName);

        if ($name === null) {
            http_response_code(404);
            echo "Sport introuvable";
            return;
        }

        require __DIR__ . '/../Vue/sport.php';
    }
}

