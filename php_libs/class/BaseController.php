<?php 

class BaseController {
	protected $type;
	protected $action;
	protected $next_type;
	protected $next_action;
	protected $file;
	protected $form;
	protected $renderer;
	protected $auth;
	protected $is_system = false;
	protected $view;
	protected $title;
	protected $message;
	protected $auth_error_mess;
	protected $login_state;
	protected $debug_str;

	public function __construct($flag=false){
		$this->set_system($flag);
		$this->view_initialize();
	}

	public function set_system($flag){
		$this->is_system = $flag;
	}

	public function view_initialize(){
		$this->view = new Smarty();
		$this->view->template_dir = _SMARTY_TEMPLATES_DIR;
		$this->view->compile_dir = _SMARTY_TEMPLATES_C_DIR;
		$this->view->config_dir = _SMARTY_CONFIG_DIR;
		$this->view->cache_dir = _SMARTY_CACHE_DIR;

		$this->form = new HTML_QuickForm();
		$this->form->setJsWarnings("入力エラーです。","上記の項目を確認してください。");
		$this->renderer = new HTML_QuickForm_Renderer_ArraySmarty($this->view);

		if(isset($_REQUEST['type'])){ $this->type = $_REQUEST['type'];}
		if(isset($_REQUEST['action'])){ $this->action = $_REQUEST['action'];}

		$this->view->assign('is_system', $this->is_system);
		$this->view->assign('SCRIPT_NAME', _SCRIPT_NAME);
		$this->view->assign('add_pageID', $this->add_pageID());
	}

	protected function view_display(){
		$this->debug_display();
		$this->disp_login_state();

		$this->view->assign('title', $this->title);
		$this->view->assign('auth_error_mess', $this->auth_error_mess);
		$this->view->assign('message', $this->message);
		$this->view->assign('disp_login_state', $this->login_state);
		$this->view->assign('type', $this->next_type);
		$this->view->assign('action', $this->next_action);
		$this->view->assign('debug_str', $this->debug_str);
		$this->form->accept($this->renderer);
		$this->view->assign('form', $this->renderer->toArray());
		$this->view->display($this->file);
	}

	public function disp_login_state(){
		if(is_object($this->auth) && $this->auth->check()){
			$this->login_state = ($this->is_system)? '管理者ログイン中':'会員ログイン中';
		}
	}

	public function make_form_controle(){
		$PrefectureModel = new PrefectureModel;
		$prefecture_array = $PrefectureModel->get_prefecture_data();
		$options = [
			'format' => 'Ymd',
			'minYear' => '1950',
			'maxYear' => date("Y")
		];
		$this->form->addElement('text', 'username', 'メール(ユーザ名))', ['size' => 30]);
		$this->form->addElement('text', 'password', 'パスワード', ['size' => 30]);
		$this->form->addElement('text', 'last_name', '氏', ['size' => 30]);
		$this->form->addElement('text', 'first_name', '名', ['size' => 30]);
		$this->form->addElement('date', 'birthday', '誕生日', $options);
		$this->form->addElement('select', 'prefecture', '県名', $prefecture_array);

		$this->form->addRule('username', 'メールアドレスを入力してください。', 'required', null, 'server');
		$this->form->addRule('username', 'メールアドレスの形式が不正です。', 'email', null, 'server');
		$this->form->addRule('password', 'パスワードを入力してください。', 'required', null, 'server');
		$this->form->addRule('password', 'パスワードは8~16文字の範囲で入力してください。','rangelength', [8,50], 'server');
		$this->form->addRule('password',  'パスワードは半角の英数字、記号（ _ - ! ? # $ % & ）を使ってください。','regex', '/^[a-zA-z0-9_\-!?#$%&]*$/', 'server');
        $this->form->addRule('last_name', '氏を入力してください。', 'required', null, 'server');
        $this->form->addRule('first_name','名を入力してください。', 'required', null, 'server'); 

        $this->form->applyFilter('__ALL__', 'trim');
	}

	public function add_pageID(){
		if(!($this->is_system && $this->type == 'list')){
			return;
		}

		$add_pageID = "";
		if(isset($_GET['pageID']) && $_GET['pageID'] != ""){
			$add_pageID = '&pageID=' . $_GET['pageID'];
			$_SESSION['pageID'] = $_GET['pageID'];
		}else if(isset($_SESSION['pageID']) && $_SESSION['pageID'] != ""){
			$add_pageID = '&pageID' . $_SESSION['pageID'];
		}
		return $add_pageID;
	}

	public function make_page_link($data){
		require_once 'Page/Jump.php';

		$params = [
			'mode' => 'Jumping',
			'perPage' => 10,
			'delta' => 10,
			'itemData' => $data
		];

		$pager = new Pager_Jumping($params);

		$data = $pager->getPageData();
		$links = $pager->getLinks();

		return [$data,$links];
	}

	public function debug_display(){
		if(_DEBUG_MODE){
			$this->debug_str = "";
			if(isset($_SESSION)){
				$this->debug_str .= '<br><br>$_SESSION<br>';
				$this->debug_str .= var_export($_SESSION, TRUE);
			}
			if(isset($_POST)){
				$this->debug_str .= '<br><br>$_POST<br>';
				$this->debug_str .= var_export($_POST, TRUE);
			}
			if(isset($_GET)){
				$this->debug_str .= '<br><br>$_GET<br>';
				$this->debug_str .= var_export($_GET, TRUE);
			}

			$this->view->debugging = _DEBUG_MODE;
		}
	}

}

?>