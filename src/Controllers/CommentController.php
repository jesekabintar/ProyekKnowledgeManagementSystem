<?php
namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Medoo\Medoo;

class CommentController
{
    public function __construct(private Medoo $db) {}

    public function add(Request $req, Response $res): Response
    {
        $data   = $req->getParsedBody();
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            return $res->withStatus(403);
        }

        // validasi rating antara 1‑5 atau NULL
        $rating = isset($data['rating']) ? (int)$data['rating'] : null;
        if ($rating !== null && ($rating < 1 || $rating > 5)) $rating = null;

        // ——— simpan komentar + rating ———
        $this->db->insert('comments', [
            'post_id'    => $data['post_id'],
            'user_id'    => $userId,
            'comment'    => trim($data['comment']),
            'rating'     => $rating,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // redirect balik ke halaman post
        return $res->withHeader('Location', '/story/' . $data['post_id'])
                   ->withStatus(302);
    }
    public function delete(Request $req, Response $res, array $args): Response
{
    $commentId = (int)$args['id'];
    $user      = $_SESSION['user'] ?? null;
    if (!$user) {
        return $res->withStatus(403);
    }

    // ambil komentar sekali untuk cek pemilik
    $comment = $this->db->get('comments', ['user_id','post_id'], ['id' => $commentId]);
    if (!$comment) {
        return $res->withStatus(404);
    }

    $isOwner = $user['id'] == $comment['user_id'];
    $isMod   = in_array($user['role'], ['admin','kontributor']);

    if (!$isOwner && !$isMod) {
        return $res->withStatus(403);  // bukan pemilik & bukan moderator
    }

    $this->db->delete('comments', ['id' => $commentId]);

    // kembali ke halaman post
    return $res->withHeader('Location', '/story/' . $comment['post_id'])
               ->withStatus(302);
}


}
