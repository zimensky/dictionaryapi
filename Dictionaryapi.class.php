<?php
/**
 * Merriam-Webster's API
 */
class Dictionaryapi {
  private $url = 'http://www.dictionaryapi.com/api/v1/references/';
  private $format = 'xml';
  private $dictionaryKey;
  private $thesaurusKey;
  private $dictionary = 'collegiate';
  private $dictionaries = array(
    'collegiate', // Collegiate Dictionary with Audio
    'thesaurus', // Collegiate Thesaurus
    'spanish', // Spanish-English Dictionary with Audio
    'medical', // Medical Dictionary with Audio
    'learners', // Learner's Dictionary with Audio
    'sd2', // Elementary Dictionary with Audio (Grades 3-5)
    'sd3', // Intermediate Dictionary with Audio (Grades 6-8)
    'sd4', // School Dictionary with Audio (Grades 9-11)
  );

  // Dictionary and key associates
  private $key = array(
    'collegiate' => 'dictionaryKey',
    'thesaurus' => 'thesaurusKey',
    'spanish' => 'dictionaryKey',
    'medical' => 'dictionaryKey',
    'learners' => 'dictionaryKey',
    'sd2' => 'dictionaryKey',
    'sd3' => 'dictionaryKey',
    'sd4' => 'dictionaryKey',
  );

  private $log = array();

  public function __construct($dictionaryKey, $thesaurusKey) {
    $this->dictionaryKey = $dictionaryKey;
    $this->thesaurusKey = $thesaurusKey;
  }

  public function getLog() {
    return $this->log;
  }

  public function clearLog() {
    $this->log = array();
  }

  /**
   * @param $dictionary string Dictionary "machine name"
   */
  public function setDictionary($dictionary) {
    if (in_array($dictionary, $this->dictionaries)) {
      $this->dictionary = $dictionary;
    } else {
      $this->log[] = 'Trying to set incorrect dictionary.';
    }
  }

  /**
   * Returns a key associated with current dictionary
   */
  private function getKey() {
    return $this->{$this->key[$this->dictionary]};
  }

  /**
   * Search the $word in current dictionary
   * @param $word string Word to search
   * @return SimpleXMLElement
   */
  public function search($word) {
    $url = $this->url . $this->dictionary . '/' . $this->format . '/' . $word . '?key=' . $this->getKey();
    $xml = simplexml_load_file($url);
    return $xml;
  }
}