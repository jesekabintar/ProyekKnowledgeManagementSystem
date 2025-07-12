<?php 
// src/Controllers/RatingController.php
namespace Controllers;

use Medoo\Medoo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RatingController
{
    private Medoo $db;

    public function __construct(Medoo $db) { $this->db = $db; }

   public function add(Request $req, Response $res): Response
{
    $data   = $req->getParsedBody();
    $postId = (int) $data['post_id'];
    $rate   = (int) $data['rating'];
    $userId = $_SESSION['user']['id'];

    // cek apakah user sudah pernah memberi rating
    $exists = $this->db->has('ratings', [
        'post_id' => $postId,
        'user_id' => $userId
    ]);

    if ($exists) {
        // update rating lama
        $this->db->update('ratings', [
            'rating' => $rate
        ], [
            'post_id' => $postId,
            'user_id' => $userId
        ]);
    } else {
        // insert rating baru
        $this->db->insert('ratings', [
            'post_id' => $postId,
            'user_id' => $userId,
            'rating'  => $rate
        ]);
    }

    /* --- hitung ulang rata‑rata & jumlah vote --- */
    $stats = $this->db->select('ratings', [
    'cnt' => Medoo::raw('COUNT(*)'),
    'avg' => Medoo::raw('AVG(rating)')
], [
    'post_id' => $postId
])[0];


    $this->db->update('posts', [
        'avg_rating'   => $stats['avg'],
        'rating_count' => $stats['cnt']
    ], [
        'id' => $postId
    ]);

    return $res
    ->withHeader('Location', "/story/$postId")
    ->withStatus(302);

}

}