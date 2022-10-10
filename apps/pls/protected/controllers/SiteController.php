<?php
/**
 * @class      SiteController
 *
 * This is the controller that contains the /site actions.
 *
 * @author     Developer
 * @copyright  PLS 3rd Learning, Inc. All rights reserved.
 */

class SiteController extends Controller {

	/**
	 * Specifies the action filters.
	 *
	 * @return array action filters
	 */
	public function filters() {
		return [
			'accessControl',
		];
	}

	/**
	 * Specifies the access control rules.
	 *
	 * @return array access control rules
	 */
	public function accessRules() {
		return [
			[
				'allow',  // allow all users to access specified actions.
				'actions' => ['index', 'login', 'about', 'error'],
				'users'   => ['*'],
			],
			[
				'allow', // allow authenticated users to access all actions
				'users' => ['@'],
			],
			[
				'deny',  // deny all users
				'users' => ['*'],
			],
		];
	}

	/**
	 * Initialize the controller.
	 *
	 * @return void
	 */
	public function init() {
		$this->defaultAction = 'login';
	}

	/**
	 * Renders the about page.
	 *
	 * @return void
	 */
	public function actionAbout() {
		$this->render('about');
	}

	/**
	 * Renders the login page.
	 *
	 * @return void
	 */
	public function actionLogin() {
		if (!defined('CRYPT_BLOWFISH') || !CRYPT_BLOWFISH) {
			throw new CHttpException(500, 'This application requires that PHP was compiled with Blowfish support for crypt().');
		}
		$model = new LoginForm();
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
		if (isset($_POST['LoginForm'])) {
			$model->attributes = $_POST['LoginForm'];
			if ($model->validate() && $model->login()) {
				$this->redirect(Yii::app()->user->returnUrl);
			}
		}

		$latestProduct = $this->getLatestFeed(Yii::app()->params['latestUpdatesFeedUrl']);
		$latestBlog = $this->getLatestFeed(Yii::app()->params['latestBlogsFeedUrl']);

		$this->render('login', ['model' => $model, 'latestProduct' => $latestProduct , 'latestBlog' => $latestBlog]);
	}

	/**
	 * Logs out the current user and redirects to homepage.
	 *
	 * @return void
	 */
	public function actionLogout() {
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}

	/**
	 * The action that handles external exceptions.
	 *
	 * @return void
	 */
	public function actionError() {
		if ($error = Yii::app()->errorHandler->error) {
			if (Yii::app()->request->isAjaxRequest) {
				echo $error['message'];
			}
			else {
				$this->render('//site/error', $error);
			}
		}
	}


	/**
	 * Convert XML object array into multidimentional array and get latest feed.
	 * 
	 * @return object
	 */
	public function getLatestFeed($url) {
		$feed = $feedArr = $feedNewArr = array();
		if ($url) {
			$feed = (array) Feed::loadRss($url);
			$feedArr = json_decode(json_encode($feed),true);
			usort($feedArr, function($a, $b) {
				return $a['pubDate'] - $b['pubDate'];
			});
		
			$feedNewArr = array_slice($feedArr[0]['item'], 0, 1, true);
			$latestFeed = json_decode(json_encode($feedNewArr));

			if (!empty($latestFeed)) {
				$more = ' <a href="' . $latestFeed[0]->link . '" target="_blank">Read more</a>';
				$latestFeed[0]->description = trim(str_replace(' [&#8230;]', '...' . $more, $latestFeed[0]->description));
				$latestFeed[0]->description = preg_replace('/The post.*appeared first on .*\./', '', $latestFeed[0]->description);
			}
		}
		return $latestFeed[0];
	}
}