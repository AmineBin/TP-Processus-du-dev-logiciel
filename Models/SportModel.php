<?php

namespace Models;

class SportModel
{
    private array $data = [
        'Jeu vidéo'     => ['Combat', 'Sport', 'Quiz', 'FPS', 'Speedrun'],
        'Sports'        => ['Basket', 'Foot', 'Tennis', 'Ping Pong', 'Athlé'],
        'Jeux de table' => ['Poker', 'Chess'],
    ];

    public function all(): array
    {
        return $this->data;
    }

    public function urlName(string $name): string
    {
        return strtolower(str_replace(' ', '-', $name));
    }

    public function findByUrlName(string $urlName): ?string
    {
        foreach ($this->data as $items) {
            foreach ($items as $name) {

                if ($this->urlName($name) === $urlName) {
                    return $name;
                }
            }
        }

        return null;
    }
}

