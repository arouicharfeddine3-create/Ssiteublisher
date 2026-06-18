<?php
namespace App\Modules\Articles;

use App\Core\Controller;
use App\Security\Auth;

class ArticleController extends Controller
{
    private ArticleService $service;

    public function __construct()
    {
        $this->service = new ArticleService();
    }

    public function index(): void
    {
        $articles = $this->service->getAllBySite(Auth::siteId());
        $this->view('articles/list', ['articles' => $articles]);
    }

    public function create(): void
    {
        $this->view('articles/create');
    }

    public function store(): void
    {
        $data = $this->validate([
            'title' => 'required',
            'content' => 'required',
        ]);
        $this->service->create($data, Auth::siteId());
        $this->redirect('/articles');
    }

    public function edit(int $id): void
    {
        $article = Article::find($id);
        if ($article === null) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $this->view('articles/edit', ['article' => $article]);
    }

    public function update(int $id): void
    {
        $data = $this->request();
        $this->service->update($id, $data);
        $this->redirect('/articles');
    }

    public function delete(int $id): void
    {
        $this->service->delete($id);
        $this->json(['success' => true]);
    }

    public function generate(): void
    {
        // Trigger AI generation via queue
        $data = $this->validate(['topic' => 'required']);
        $articleId = $this->service->createFromTopic($data['topic'], Auth::siteId());
        // Dispatch job
        $dispatcher = new \App\Queue\JobDispatcher();
        $dispatcher->dispatch(\App\Queue\Jobs\GenerateArticleJob::class, ['article_id' => $articleId]);
        $this->json(['message' => 'Generation started', 'article_id' => $articleId]);
    }
}