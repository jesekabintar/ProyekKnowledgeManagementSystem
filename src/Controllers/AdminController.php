<?php

namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AdminController
{
    private $view;
    private $db;

    public function __construct(Twig $view,  \Medoo\Medoo $db)
    {
        $this->view = $view;
         $this->db = $db;
    }

    public function index(Request $request, Response $response): Response
{
    $user = $_SESSION['user'] ?? null;

    $users = $this->db->select("users", "*", [
        "deleted_at" => null
    ]);

    return $this->view->render($response, 'admin/dashboard.twig', [
    'total_users' => $this->db->count('users'),
    'total_posts' => $this->db->count('posts'),
    'total_categories' => $this->db->count('categories'),
    'users' => $users,
    'session' => $_SESSION
]);


}

public function editUser(Request $request, Response $response, array $args): Response
{
    $id = $args['id'];

    $user = $this->db->get("users", "*", [
        "id" => $id,
        "deleted_at" => null
    ]);

    if (!$user) {
        // Redirect or 404
        return $response->withHeader('Location', '/admin')->withStatus(302);
    }

    return $this->view->render($response, 'admin/user_edit.twig', [
        'title' => 'Edit Pengguna',
        'user' => $user
    ]);
}


public function updateUser(Request $request, Response $response, array $args): Response
{
    $id = $args['id'];
    $data = $request->getParsedBody();

    $this->db->update("users", [
        "username" => $data['username'],
        "role" => $data['role']
    ], [
        "id" => $id
    ]);

    return $response->withHeader('Location', '/admin')->withStatus(302);
}




    public function deleteUser(Request $request, Response $response, array $args)
{
    $id = $args['id'];

    $this->db->update("users", [
        "deleted_at" => date("Y-m-d H:i:s")
    ], [
        "id" => $id
    ]);

    return $response->withHeader('Location', '/admin')->withStatus(302);
}

public function manageCategoryTag(Request $request, Response $response): Response
{
    $categories = $this->db->select("categories", "*");
    $tags = $this->db->select("tags", "*");

    return $this->view->render($response, 'admin/manage_category_tag.twig', [
        'categories' => $categories,
        'tags' => $tags,
        'title' => 'Kelola Kategori & Tag'
    ]);
}

public function storeCategoryTag(Request $request, Response $response): Response
{
    $data = $request->getParsedBody();

    if (!empty($data['category'])) {
        $this->db->insert("categories", ["name" => $data['category']]);
    }

    if (!empty($data['tag'])) {
        $this->db->insert("tags", ["name" => $data['tag']]);
    }

    return $response->withHeader('Location', '/admin/kategori-tag')->withStatus(302);
}

/* ---------- CATEGORY ---------- */
public function editCategory(Request $req, Response $res, array $args): Response {
    $cat = $this->db->get('categories', '*', ['id' => $args['id']]);
    return $this->view->render($res, 'admin/category_edit.twig', [
        'title'=>'Edit Kategori',
        'category'=>$cat
    ]);
}

public function updateCategory(Request $req, Response $res, array $args): Response {
    $data = $req->getParsedBody();
    $this->db->update('categories', ['name'=>$data['name']], ['id'=>$args['id']]);
    return $res->withHeader('Location','/admin/kategori-tag')->withStatus(302);
}

public function deleteCategory(Request $req, Response $res, array $args): Response {
    // opsional: blokir jika masih dipakai post
    $count = $this->db->count('posts',['category_id'=>$args['id']]);
    if($count==0){
        $this->db->delete('categories',['id'=>$args['id']]);
    }
    return $res->withHeader('Location','/admin/kategori-tag')->withStatus(302);
}

/* ---------- TAG ---------- */
public function editTag(Request $req, Response $res, array $args): Response {
    $tag = $this->db->get('tags','*',['id'=>$args['id']]);
    return $this->view->render($res,'admin/tag_edit.twig',[
        'title'=>'Edit Tag',
        'tag'=>$tag
    ]);
}

public function updateTag(Request $req, Response $res, array $args): Response {
    $data = $req->getParsedBody();
    $this->db->update('tags',['name'=>$data['name']],['id'=>$args['id']]);
    return $res->withHeader('Location','/admin/kategori-tag')->withStatus(302);
}

public function deleteTag(Request $req, Response $res, array $args): Response {
    $this->db->delete('tags',['id'=>$args['id']]);
    $this->db->delete('post_tags',['tag_id'=>$args['id']]); // bersih‑bersih relasi
    return $res->withHeader('Location','/admin/kategori-tag')->withStatus(302);
}




}
