<?php

class ErrorPage {
	static function render($error_code)
	{
		return file_get_contents(CODE_DIR.'res/'.$error_code.'.html');
	}
}

class Page {
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
		if ($key == 'tags') $this->payload['tags'] = explode(' ', $value);
		else $this->payload[$key] = $value;
		return '';
	}
	
	function get_payload()
	{
		return $this->payload;
	}
	
	function load_file($filename, $is_post = false)
	{
		$this->file = $filename;
		$this->payload = array();
		$raw = file_get_contents($filename);
		$raw = preg_replace_callback('/{{(.*?):(.*?)}}/', array($this, 'add_payload'), $raw);
		$this->payload['is_post'] = $is_post;
		$this->payload['content'] = Markdown($raw);
	}
	
	function render()
	{
		global $page_layout;
		if ($this->resp_code != 200) return ErrorPage::render($this->resp_code);
		if (strtolower($this->payload['draft']) == 'hide') return ErrorPage::render(404);
		if (isset($this->payload['layout'])) $layout = $this->payload['layout'];
		else $layout = $page_layout;
		$layout = file_get_contents(LAYOUT_DIR.$layout.'.html');
		$m = new Mustache();
		return $m->render($layout, $this->payload);
	}
}

class BlogPage {
	private $filter;
	
	function __construct($filter = "")
	{	
		$this->filter = $filter;
	}
	
	function render()
	{
		global $blog_layout, $blog_slug;
		$layout = file_get_contents(LAYOUT_DIR.$blog_layout.'.html');
		$payload = array('posts' => array(), 'blog_filter' => $this->filter);
		$page = new Page();
		$handle = opendir(POST_DIR);
		while (($file = readdir($handle))) {
			if (strtolower(substr($file, strpos($file, '.'))) != '.md') continue;
			$page->load_file(POST_DIR.$file, true);
			$post = $page->get_payload();
			if ((empty($this->filter) || in_array($this->filter, $post['tags'])) && !isset($post['draft'])) {
				$post['content'] = substr($post['content'], 0, strpos($post['content'], '</p>'));
				$post['content'] .= '...<br /><a href="/'.$blog_slug.'/'.substr($file, 0, strpos($file, '.')).'" class="more">Read More</a></p>';
				array_push($payload['posts'], $post);
			}
		}
		$payload['posts'] = array_reverse($payload['posts']);
		$m = new Mustache();
		return $m->render($layout, $payload);
	}
}