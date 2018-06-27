<?php
$app->get('/', function ($request, $responsep) {

});

$app->get('/site/session', function ($request, $response) {
    if (isset($_SESSION['user']['id'])) {
        return successResponse($response, $_SESSION);
    }
    return unprocessResponse($response, ['undefined']);
})->setName("session");

$app->post('/site/login', function ($request, $response) {
    $params = $request->getParams();
    $sql    = $this->db;

    $username = isset($params['username']) ? $params['username'] : '';
    $password = isset($params['password']) ? $params['password'] : '';

    $model = $sql->select("m_pengguna.*,m_pengguna_akses.akses")
        ->from("m_pengguna")
        ->where("username", "=", $username)
        ->andWhere("password", "=", sha1($password))
        ->leftJoin("m_pengguna_akses", "m_pengguna_akses.id = m_pengguna.m_pengguna_akses_id")
        ->find();

    if (!empty($model)) {
        $_SESSION['user']['id']                  = $model->id;
        $_SESSION['user']['username']            = $model->username;
        $_SESSION['user']['nama']                = $model->nama;
        $_SESSION['user']['m_pengguna_akses_id'] = $model->m_pengguna_akses_id;
        $_SESSION['user']['akses']               = json_decode($model->akses);

        return successResponse($response, $_SESSION);
    }
    return unprocessResponse($response, ['Authentication Systems gagal, username atau password Anda salah.']);
})->setName("login");

$app->get('/site/logout', function () {
    session_destroy();
})->setName("logout");

$app->get('/site/domain', function ($request, $response) {   
    $url = implode('.', array_slice(explode('.', $_SERVER['HTTP_HOST']), -2));
    return successResponse($response, ['url' => $url]);
});