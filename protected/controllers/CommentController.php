<?php

class CommentController extends Controller
{
    public $layout = 'column2';

    /**
     * @var CActiveRecord the currently loaded data model instance.
     */
    private $_model;

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
        return array(
            array('allow', // allow authenticated users to access all actions
                'users' => array('@'),
                'actions' => array('rating'),
            ),
            array('deny',  // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     */
    public function actionUpdate()
    {
        $model = $this->loadModel();
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'comment-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
        if (isset($_POST['Comment'])) {
            $model->attributes = $_POST['Comment'];
            if ($model->save())
                $this->redirect(array('index'));
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     */
    public function actionDelete()
    {
        if (Yii::app()->request->isPostRequest) {
            // we only allow deletion via POST request
            $this->loadModel()->delete();

            // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
            if (!isset($_POST['ajax']))
                $this->redirect(array('index'));
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        $dataProvider = new CActiveDataProvider('Comment', array(
            'criteria' => array(
                'with' => 'post',
                'order' => 't.status, t.create_time DESC',
            ),
        ));

        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Approves a particular comment.
     * If approval is successful, the browser will be redirected to the comment index page.
     */
    public function actionApprove()
    {
        if (Yii::app()->request->isPostRequest) {
            $comment = $this->loadModel();
            $comment->approve();
            $this->redirect(array('index'));
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }


    public function actionRating()
    {
        if (Yii::app()->request->isAjaxRequest) {
            $rating = new Rating();
            $model = Comment::model()->findbyPk($_POST['comment_id']);
            if ($_POST['vote_type'] == 0) {
                $model->rating_sum = $model->rating_sum + 1;
                $rating->comment_id = $model->id;
                $rating->user_id = Yii::app()->user->id;
                $rating->vote_type = 0;

            } elseif ($_POST['vote_type'] == 1) {
                $model->rating_sum = $model->rating_sum - 1;
                $rating->comment_id = $model->id;
                $rating->user_id = Yii::app()->user->id;
                $rating->vote_type = 1;
            }
            $model->rating_count = $model->rating_count + 1;

            if ($rating->save()) {
                $model->save();
                echo CJSON::encode(array(
                    'status' => 'success',
                    'count' => $model->rating_count,
                    'rating' => $model->rating_sum,

                ));
            } else
                if ($rating->vote_type == 0)
                    echo CJSON::encode(array(
                        'status' => 'failure',
                        'count' => $model->rating_count - 1,
                        'rating' => $model->rating_sum - 1
                    ));
                elseif ($rating->vote_type == 1)
                    echo CJSON::encode(array(
                        'status' => 'failure',
                        'count' => $model->rating_count - 1,
                        'rating' => $model->rating_sum + 1
                    ));

        }
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     */
    public function loadModel()
    {
        if ($this->_model === null) {
            if (isset($_GET['id']))
                $this->_model = Comment::model()->findbyPk($_GET['id']);
            if ($this->_model === null)
                throw new CHttpException(404, 'The requested page does not exist.');
        }
        return $this->_model;
    }
}
