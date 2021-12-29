<?php

namespace Drupal\zoo\Controller;

use Drupal\Core\Controller\ControllerBase;

class AnimalListController extends ControllerBase {

/**
* @var \Drupal\Core\Database\Connection
*/

private $connection;

function __construct(\Drupal\Core\Database\Connection $connection){
    $this->connection = $connection;    
}

public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container){
    return new static(
        $container->get('database')
    );
}

    public function listAnimals() {
        $header = [
            $this->t('Name'),
            $this->t('Type'),
            $this->t('Age'),
            $this->t('Weight'),
            $this->t('Habitat'),
        ];
        $rows = [];

        $results = $this->connection->query("SELECT a.*, h.name AS habitat_name
        FROM {zoo_animal} a
        LEFT JOIN {zoo_habitat} h ON a.habitat_id = h.habitat_id
        ORDER BY h.name");
        foreach ($results as $record){
            $age = floor((REQUEST_TIME - $record->birthdate) / (365 * 24 *360));
            $rows[] = [
                $record->name,
                $record->type,
                $this->t('@age years', ['@age' => $age]),
                $this->t('@weight kg', ['@weight' => $record->weight]),
                $record->habitat_name,
            ];
        }

        //query for the animals and return an array

        return [
            '#theme' => 'table',
            '#rows' => $rows,
            '#header' => $header,
        ];
    }

    public function listAnimalsInHabitat($habitat) {
        $header = [
            $this->t('Name'),
            $this->t('Type'),
            $this->t('Age'),
            $this->t('Weight'),
        ];
        $rows = [];

        $query = $this->connection->select('zoo_animal','a')
            ->fields('a')
            ->orderBy('name','ASC')
            ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
            ->limit(3);
            
        if($habitat != 'all'){
            $query->condition('a.habitat_id',$habitat);
        } else {
            $query->leftJoin('zoo_habitat','h','a.habitat_id = h.habitat_id');
            $query->addField('h','name','habitat_name');
            $header[] = $this->t('Habitat');
        }
        $results = $query->execute();

        foreach ($results as $record){
            $age = floor((REQUEST_TIME - $record->birthdate) / (365 * 24 *360));
            $row = [
                \Drupal\Core\Link::fromTextAndUrl(
                    $record->name,
                    \Drupal\Core\Url::fromRoute(
                        'zoo.animal_view', ['animal_id' => $record->animal_id]
                    )    
                ),
                $record->type,
                $this->t('@age years', ['@age' => $age]),
                $this->t('@weight kg', ['@weight' => $record->weight]),
            ];
            if($habitat == 'all'){
                $row[] = \Drupal\Core\Link::fromTextAndUrl(
                    $record->habitat_name,
                    \Drupal\Core\Url::fromRoute('zoo.habitats_list', ['habitat' => $record->habitat_id]),
                );
            }
            $rows[] = $row;
        }

        //query for the animals and return an array

        return [
            'data' => [
                '#theme' => 'table',
                '#rows' => $rows,
                '#header' => $header,
            ],
            'pager' => [
                '#type' => 'pager',
            ],
        ];
    }

    public function listAnimalsInHabitatTitle($habitat){
        if($habitat == 'all'){
            return $this->t('Animals in all habitats');
        }
        $name = $this->connection->select('zoo_habitat','h')
            ->fields('h',['name'])
            ->condition('h.habitat_id',$habitat, '=')
            ->execute()
            ->fetchField();

        if (!empty($name)){
        return $this->t('Animals in @habitat', ['@habitat' => $name]);
        }
        return $this->t('Habitat not found');
    }


    public function listAnimalsInHabitatStaticAPI($habitat) {
        $header = [
            $this->t('Name'),
            $this->t('Type'),
            $this->t('Age'),
            $this->t('Weight'),
        ];
        $rows = [];

        $results = $this->connection->query("SELECT * FROM {zoo_animal}
        WHERE habitat_id = :habitat_id ORDER BY name",
        [
            'habitat_id' => $habitat,
        ]);
        foreach ($results as $record){
            $age = floor((REQUEST_TIME - $record->birthdate) / (365 * 24 *360));
            $rows[] = [
                $record->name,
                $record->type,
                $this->t('@age years', ['@age' => $age]),
                $this->t('@weight kg', ['@weight' => $record->weight]),
            ];
        }

        //query for the animals and return an array

        return [
            '#theme' => 'table',
            '#rows' => $rows,
            '#header' => $header,
        ];
    }

    public function listAnimalsInHabitatTitleStaticAPI($habitat){
        $name = $this->connection->query("SELECT name FROM {zoo_habitat}
            WHERE habitat_id = :habitat_id",
            [
                'habitat_id' => $habitat,
            ])->fetchField();
        if (!empty($name)){
        return $this->t('Animals in @habitat', ['@habitat' => $name]);
        }
        return $this->t('Habitat not found');

    }

    public function demoInsert(){
        //single INSERT 
        $fields = [
            'name' => 'Mike',
            'type' => 'Monkey',
            'birthdate' => strtotime('2015-06-17'),
            'weight' => 5.3,
            'description' => 'Mike is great',
            'habitat_id' => 4,
        ];
        $this->connection->insert('zoo_animal')
            ->fields($fields)
            ->execute();
        //MULTIPLE INSERT WITH SINGLE QUERY
        $fields = [
            'name',
            'type',
            'birthdate',
            'weight',
            'description',
            'habitat_id',  
        ];  
        $query = $this->connection->insert('zoo_animal')
            ->fields($fields);
        
        $value1 = [
            'name' => 'Polly',
            'type' => 'Parrotfish',
            'birthdate' => strtotime('2013-04-14'),
            'weight' => 8.1,
            'description' => 'Polly is colorful',
            'habitat_id' => 2,    
        ];
        $query->values($value1); 
        
        $value2 = [
            'name' => 'Maggie',
            'type' => 'Macaw',
            'birthdate' => strtotime('2014-09-13'),
            'weight' => 2.5,
            'description' => 'Maggie is wild',
            'habitat_id' => 3,    
        ];
        $query->values($value2); 
        $query->execute();
        
        return [
            '#markup' => $this->t('Insert Completed'),
        ];
    }

    public function demoUpdate(){
        $fields_to_change = [
            'type' => 'Howler Monkey',
            'weight' => 6.7,
        ];
        $this->connection->update('zoo_animal')
            ->fields($fields_to_change)
            ->condition('name','Mike')
            ->execute();
        return [
            '#markup' => $this->t('Updates complete'),
        ];    
    }

    public function demoDelete() {
        $this->connection->delete('zoo_animal')
            ->condition('name', 'Mike')
            ->execute();
        $this->connection->delete('zoo_animal')
            ->condition('name', ['Polly','Maggie'], 'IN')
            ->execute();   
        return [
            '#markup' => $this->t('Deletes completed'),
        ];     
    }

}