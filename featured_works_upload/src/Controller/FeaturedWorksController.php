<?php
	
namespace Drupal\featured_works_upload\Controller;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Config\Entity\Query\Query;
use Druapl\Core\TypedData\TypedDataInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class FeaturedWorksController{
	private $status, $data, $connection, $table, $nids;
    
	//fetch nodes from DB
	public function __construct(){
		$this->connection = \Drupal::database();
		$this->nids = $this->connection->query("SELECT nid FROM node WHERE type = 'featured_work'")->fetchAll();
	}

	public function getAllNodes(){
		$arrToSend = [];
		foreach( $this->nids as $i){
			$node = Node::load($i->nid);
			$data = [
				'status' => $node->get('field_application_status')->value,
				'title' => $node->get('title')->value,
				'nid' => $node->get('nid')->value,
				'name' => $node->get('field_name')->value,
				'class' => $node->get('field_class_name')->value,
				'link' => $node->get('field_project_link')->value
			];
			array_push($arrToSend, $data);
		}
		return $arrToSend;
	}
	
	//render the pending uploads as a page
    public function renderWork(){
		$nodes = $this->getAllNodes();
        return(array(
            '#theme' => 'featured-works-template',
			'#baseURL' => base_path(),
			'#nodes' => $nodes
        ));
    }

	//approve nodes from the module
	//handle this from drupal side to aaporve work students submit
	public function changeStatus(Request $request){
		$nid = $request->query->get('nid');
		$node = Node::load($nid);
		$node->set('field_application_status', 1);
		$node->set('status', 1);
		$node->save();
		return array();
	}

	private function setImage($imgStream){
		$stream = base64_decode($imgStream['stream']); 
		$uri = 'public://student-projects/'. $imgStream['name'];
		$file = File::create([
			'uid' => 0,
			'uri' => $uri,
			'filename' => $imgStream['name'],
			'filemime' => $imgStream['type'],
			'status' => 1,
			'filesize' => $imgStream['size']
		]);
		$dir = dirname($file->getFileURI());
		if(!file_exists($dir)){
			mkdir($dir, 0770, TRUE);
		}
		$i = 1;
		while(file_exists($file->getFileURI())){
			$uri = 'public://student-projects/' . $i . "-" . $imgStream['name'];
			$file->set('uri', $uri);
			$i++;
		}
		try{
			file_put_contents($file->getFileURI(), $stream); 
			$file->save();
			return $file->id();
		}
		catch(Exception $e){
			\Drupal::logger('Featured Works')->error($e->getMessage());
		}
	}
	
	//create nodes from uploads
	//the data comes from the Gatsby site
	public function addEvent(Request $request){
		//post data in body
		$body = json_decode($request->getContent(), true)['data'];
		$imgStream = json_decode($request->getContent(), true)['img'];

		try{
			$node = Node::create([
				'type' => 'featured_work',
				'title' => $body['title'],
				'body' => $body['description'],
				'field_class_name' => $body['class'],
				'field_name' => $body['name'],
				'field_project_link' => $body['link'],
				'field_project_picture' => $this->setImage($imgStream),
				'field_application_status' => 0
			]);
			$node->status = 0; //Do not publish on creation
			$node->enforceIsNew();
			$node->save();
			return new Response(json_encode(['status' => 'success']), Response::HTTP_OK, ['content-type' => 'application/json']);
		}
		catch(Exception $e){
			return new Response(json_encode(['status' => $e->getMessage()]), Response::HTTP_OK, ['content-type' => 'application/json']);
		}
	}	
}