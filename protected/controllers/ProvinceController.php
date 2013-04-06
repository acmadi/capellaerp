<?php

class ProvinceController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column1';
protected $menuname = 'province';

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
	  parent::actionCreate();
      $model=new Province;

      if (Yii::app()->request->isAjaxRequest)
      {
          echo CJSON::encode(array(
              'status'=>'success',
              ));
          Yii::app()->end();
      }
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate()
	{
	  parent::actionUpdate();
	  $model=$this->loadModel($_POST['id']);
      if ($model != null)
      {
        if ($this->CheckDataLock($this->menuname, $_POST['id']) == false)
        {
          $this->InsertLock($this->menuname, $_POST['id']);
          echo CJSON::encode(array(
              'status'=>'success',
              'provinceid'=>$model->provinceid,
              'countryid'=>$model->countryid,
              'countryname'=>($model->country!==null)?$model->country->countryname:"",
              'provincename'=>$model->provincename,
              'recordstatus'=>$model->recordstatus,
              ));
          Yii::app()->end();
        }
      }
	}

    public function actionCancelWrite()
    {
      $this->DeleteLockCloseForm($this->menuname, $_POST['Province'], $_POST['Province']['provinceid']);
    }

	public function actionWrite()
	{
	  parent::actionWrite();
	  if(isset($_POST['Province']))
	  {
        $messages = $this->ValidateData(
                array(array($_POST['Province']['countryid'],'emptycountryname','emptystring'),
                array($_POST['Province']['provincename'],'emptyprovincename','emptystring'),
            )
        );
        if ($messages == '') {
          //$dataku->attributes=$_POST['Province'];
          if ((int)$_POST['Province']['provinceid'] > 0)
          {
            $model=$this->loadModel($_POST['Province']['provinceid']);
			$this->olddata = $model->attributes;
			$this->useraction='update';
            $model->provincename = $_POST['Province']['provincename'];
            $model->countryid = $_POST['Province']['countryid'];
            $model->recordstatus = $_POST['Province']['recordstatus'];
          }
          else
          {
            $model = new Province();
            $model->attributes=$_POST['Province'];
			$this->olddata = $model->attributes;
			$this->useraction='new';
          }
		  $this->newdata = $model->attributes;
          try
          {
            if($model->save())
            {
			  $this->InsertTranslog();
              $this->DeleteLock($this->menuname, $_POST['Province']['provinceid']);
              $this->GetSMessage('insertsuccess');
            }
            else
            {
              $this->GetMessage($model->getErrors());
            }
          }
          catch (Exception $e)
          {
            $this->GetMessage($e->getMessage());
          }
        }
	  }
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete()
	{
	  parent::actionDelete();
		$model=$this->loadModel($_POST['id']);
		  $model->recordstatus=0;
		  $model->save();
		echo CJSON::encode(array(
                'status'=>'success',
                'div'=>'Data deleted'
				));
        Yii::app()->end();
	}
	
	protected function gridData($data,$row)
  {     
    $model = Province::model()->findByPk($data->provinceid); 
    return $this->renderPartial('_view',array('model'=>$model),true); 
  }

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
	  parent::actionIndex();
    $model=new Province('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Province']))
			$model->attributes=$_GET['Province'];
if (isset($_GET['pageSize']))
	  {
		Yii::app()->user->setState('pageSize',(int)$_GET['pageSize']);
		unset($_GET['pageSize']);  // would interfere with pager and repetitive page size change
	  }
		$this->render('index',array(
			'model'=>$model,
		));
	}

 public function actionDownload()
	{
		parent::actionDownload();
		$sql = "select countryname,provincename
				from province a 
				left join country b on b.countryid = a.countryid ";
		if ($_GET['id'] !== '') {
				$sql = $sql . "where a.provinceid = ".$_GET['id'];
		}
		$command=$this->connection->createCommand($sql);
		$dataReader=$command->queryAll();

		$this->pdf->title='Province List';
		$this->pdf->AddPage('P');

		$this->pdf->colalign = array('C','C');
		$this->pdf->setwidths(array(70,50));
		$this->pdf->colheader = array('Country Name','Province Name');
		$this->pdf->RowHeader();
		$this->pdf->coldetailalign = array('L','L');
		foreach($dataReader as $row1)
		{
		  $this->pdf->row(array($row1['countryname'],$row1['provincename']));
		}
		// me-render ke browser
		$this->pdf->Output();
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=Province::model()->findByPk((int)$id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='province-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
