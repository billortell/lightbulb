<?php

class ErrorPage {
	static function render($error_code)
	{
		global $site_root;
		include CODE_DIR.'res/'.$error_code.'.php';
	}
}

class BasePage {
	function render($layout, $payload)
	{
		global $site_root, $blog_slug, $tag_slug, $template_engine;
		if ($template_engine == 'mustache') {
			$engine = new Mustache();
			$layout = file_get_contents(LAYOUT_DIR.$layout.'.html');
		} elseif ($template_engine == 'twig') {
			Twig_Autoloader::register();
			$engine = new Twig_Environment(new Twig_Loader_Filesystem(LAYOUT_DIR));
			$layout .= '.html';
		} else {
			return ErrorPage::render(500);
		}
		return $engine->render($layout, array_merge($payload,
			array('site' => array('root' => $site_root, 'blog' => $blog_slug, 'tag' => $tag_slug))
			));
	}
}

class Page extends BasePage {
	private $request;
	private $file;
	private $payload;
	private $resp_code = 200;
	
	function __construct($request, $root = PAGE_DIR)
	{
		if (empty($request)) $request = 'index';
		$this->request = $request;
		if (!file_exists($root.$request.'.md')) {
			$request .= '/index';
			if (!file_exists($root.$request.'.md')) {
				$this->resp_code = 404;
				return;
			}
		}
		$this->resp_code = 200;
		$this->load_file($root.$request.'.md', ($root == POST_DIR));
	}
	
	private function add_payload($matches)
	{
		$key = strtolower(trim($matches[1]));
		$value = trim($matches[2]);
		if ($key == 'tags') $this->payload['meta']['tags'] = explode(' ', $value);
		else $this->payload['meta'][$key] = $value;
		return '';
	}
	
	function get_payload()
	{
		return $this->payload;
	}
	
	function load_file($filename, $is_post = false)
	{
		$this->file = $filename;
		$this->payload = array('meta' => array());
		$raw = file_get_contents($filename);
		$raw = preg_replace_callback('/{{(.*?):(.*?)}}/', array($this, 'add_payload'), $raw);
		$this->payload['is_post'] = $is_post;
		$this->payload['content'] = Markdown($raw);
	}
	
	function render()
	{
		global $page_layout;
		if ($this->resp_code != 200) return ErrorPage::render($this->resp_code);
		if (strtolower($this->payload['meta']['draft']) == 'hide') return ErrorPage::render(404);
		if (isset($this->payload['meta']['layout'])) $layout = $this->payload['meta']['layout'];
		else $layout = $page_layout;
		return parent::render($layout, $this->payload);
	}
}

class BlogPage extends BasePage {
	private $filter;
	
	function __construct($filter = "")
	{	
		$this->filter = $filter;
	}
	
	function render()
	{
		global $site_root, $blog_layout, $blog_slug;
		$payload = array('is_blog' => true, 'posts' => array(), 'blog_filter' => $this->filter);
		$page = new Page();
		$handle = opendir(POST_DIR);
		while (($file = readdir($handle))) {
			if (strtolower(substr($file, strpos($file, '.'))) != '.md') continue;
			$page->load_file(POST_DIR.$file, true);
			$post = $page->get_payload();
			if ((empty($this->filter) || in_array($this->filter, $post['meta']['tags'])) && !isset($post['meta']['draft'])) {
				$post['content'] = substr($post['content'], 0, strpos($post['content'], '</p>'));
				$post['content'] .= '...<br /><a href="'.$site_root.$blog_slug.'/'.substr($file, 0, strpos($file, '.')).'" class="more">Read More</a></p>';
				array_push($payload['posts'], $post);
			}
		}
		$payload['posts'] = array_reverse($payload['posts']);
		return parent::render($blog_layout, $payload);
	}
}