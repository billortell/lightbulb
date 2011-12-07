<?php

class ErrorPage {
	static function render($error_code)
	{
		return file_get_contents(CODE_DIR.'res/'.$error_code.'.html');
	}
}

class Page {
	private $root;
	private $request;
	private $file;
	private $is_post = false;
	private $payload;
	private $resp_code = 200;
	
	function __construct($request, $root = PAGE_DIR)
	{
		$this->root = $root;
		if (empty($request)) $request = 'index';
		$this->request = $request;
		if ($root == POST_DIR) $this->is_post = true;
		if (!file_exists($root.$request.'.md')) {
			$request .= '/index';
			if (!file_exists($root.$request.'.md')) {
				$this->resp_code = 404;
				return;
			}
		}
		$this->resp_code = 200;
		$this->load_file($root.$request.'.md');
	}
	
	private function add_payload($item)
	{
		if ($item[1] == 'tags') $this->payload['tags'] = explode(' ', $item[2]);
		else $this->payload[$item[1]] = $item[2];
		return '';
	}
	
	function load_file($filename)
	{
		$this->file = $filename;
		$this->payload = array();
		$raw = file_get_contents($filename);
		$raw = preg_replace_callback('/{{(.*?):(.*?)}}/', array($this, 'add_payload'), $raw);
		$this->payload['content'] = Markdown($raw);
	}
	
	function get_payload()
	{
		return $this->payload;
	}
	
	function render()
	{
		global $page_layout;
		if ($this->resp_code != 200) return ErrorPage::render($this->resp_code);
		$layout = $page_layout;
		if (isset($this->payload['layout'])) $layout = $this->payload['layout'];
		$layout = file_get_contents(LAYOUT_DIR.$layout.'.html');
		$m = new Mustache();
		return $m->render($layout, array_merge($this->payload, array('is_post' => $this->is_post)));
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
		$payload = array('posts' => array());
		$page = new Page();
		$handle = opendir(POST_DIR);
		while (($file = readdir($handle))) {
			if (strtolower(substr($file, strpos($file, '.'))) != '.md') continue;
			$page->load_file(POST_DIR.$file);
			$post = $page->get_payload();
			if (empty($this->filter) || in_array($this->filter, $post['tags'])) {
				$post['content'] = substr($post['content'], 0, strpos($post['content'], '</p>'));
				$post['content'] .= '...<br /><a href="/'.$blog_slug.'/'.substr($file, 0, strpos($file, '.')).'" class="more">Read More</a></p>';
				array_push($payload['posts'], $post);
			}
		}
		$payload['posts'] = array_reverse($payload['posts']);
		$m = new Mustache();
		return $m->render($layout, array_merge($payload, array('blog_filter' => $this->filter)));
	}
}