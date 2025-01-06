<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\taxonomy\VocabularyInterface;

/**
 * Ensures that the term pager works properly.
 *
 * @group taxonomy
 * @group failingTest
 */
class TaxonomyTermReorderTest extends TaxonomyTestBase {
  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected VocabularyInterface $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy', 'bypass node access']));
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Change order of terms & update a term using the user interface.
   */
  public function testTermReorderAndUpdate() {
    $assert = $this->assertSession();
    $this->createTerm($this->vocabulary, ['name' => 'Alpha']);
    $this->createTerm($this->vocabulary, ['name' => 'Beta']);
    $this->createTerm($this->vocabulary, ['name' => 'Charlie']);
    $this->createTerm($this->vocabulary, ['name' => 'Delta']);

    $taxonomy_storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');

    // Fetch the created terms in the default alphabetical order, i.e. term1,
    // term2, term3 & term4.
    [$term1, $term2, $term3, $term4] = $taxonomy_storage->loadTree($this->vocabulary->id(), 0, NULL, TRUE);

    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');

    $page = $this->getSession()->getPage();

    // Each term has four hidden fields, "tid:1:0[tid]", "tid:1:0[parent]",
    // "tid:1:0[depth]", and "tid:1:0[weight]". Change the order to
    // term4 (Delta), term2 (Beta), term3 (Charlie), term1 (Alpha), by setting
    // weight property. Make term3 a child of term2 by setting the parent
    // and depth properties, and update all hidden fields.
    $fields = [
      'terms[tid:' . $term2->id() . ':0][term][tid]' => $term2->id(),
      'terms[tid:' . $term2->id() . ':0][term][parent]' => 0,
      'terms[tid:' . $term2->id() . ':0][term][depth]' => 0,
      'terms[tid:' . $term2->id() . ':0][weight]' => 1,
      'terms[tid:' . $term3->id() . ':0][term][tid]' => $term3->id(),
      'terms[tid:' . $term3->id() . ':0][term][parent]' => $term2->id(),
      'terms[tid:' . $term3->id() . ':0][term][depth]' => 1,
      'terms[tid:' . $term3->id() . ':0][weight]' => 0,
      'terms[tid:' . $term4->id() . ':0][term][tid]' => 0,
      'terms[tid:' . $term4->id() . ':0][term][parent]' => 0,
      'terms[tid:' . $term4->id() . ':0][term][depth]' => 0,
      'terms[tid:' . $term4->id() . ':0][weight]' => 0,
      'terms[tid:' . $term1->id() . ':0][term][tid]' => $term1->id(),
      'terms[tid:' . $term1->id() . ':0][term][parent]' => 0,
      'terms[tid:' . $term1->id() . ':0][term][depth]' => 0,
      'terms[tid:' . $term1->id() . ':0][weight]' => 2,
    ];

    foreach ($fields as $field => $value) {
      $page->find('css', '[name="' . $field . '"]')->setValue($value);
    }

    $page->pressButton((string) t('Save'));

    // Asserts the order & hierarchy has been saved & show new order in the UI.
    $assert->fieldValueEquals('terms[tid:4:0][weight]', '1');
    $assert->fieldValueEquals('terms[tid:2:0][weight]', '2');
    $assert->fieldValueEquals('terms[tid:3:0][weight]', '0');
    $assert->hiddenFieldValueEquals('terms[tid:3:0][term][parent]', '2');
    $assert->fieldValueEquals('terms[tid:1:0][weight]', '3');

    // Reload terms to prevent usage of cached loaded terms.
    $taxonomy_storage->resetCache();

    // Asserts, loadTree return the new order.
    $terms = $taxonomy_storage->loadTree($this->vocabulary->id());
    $this->assertEquals($term4->id(), $terms[0]->tid, 'Term 4 is the first term.');
    $this->assertEquals($term2->id(), $terms[1]->tid, 'Term 2 was moved above term 4.');
    $this->assertEquals([$term2->id()], $terms[2]->parents, 'Term 3 was made a child of term 2.');
    $this->assertEquals($term1->id(), $terms[3]->tid, 'Term 1 was moved below term 2.');

    // Then reset terms to alphabetical order.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->submitForm([], t('Reset to alphabetical'));
    // Submit confirmation form.
    $this->submitForm([], t('Reset to alphabetical'));
    // Ensure form redirected back to overview.
    $assert->addressEquals('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');

    // Updating the Term2 (Beta) should not alter the order.
    // By updating the Term2 we asserts the previous "Reset to alphabetical"
    // action has reset the term cache & prevent usage of cached terms weight.
    $this->drupalGet('taxonomy/term/' . $term2->id() . '/edit');
    $this->submitForm([], t('Save'));

    // Return on the vocabulary overview page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $page = $this->getSession()->getPage();

    // Asserts the new weight are set to 0 on the UI & order stay unchanged.
    $assert->fieldValueEquals('terms[tid:2:0][weight]', '0');
    $assert->fieldValueEquals('terms[tid:3:0][weight]', '0');
    $assert->hiddenFieldValueEquals('terms[tid:3:0][term][parent]', '2');
    $assert->fieldValueEquals('terms[tid:4:0][weight]', '0');
    $assert->fieldValueEquals('terms[tid:1:0][weight]', '0');

    // Reload terms to prevent usage of cached loaded terms.
    $taxonomy_storage->resetCache();

    // Asserts, after reset the internal cache, loadTree return the new order.
    $terms = $taxonomy_storage->loadTree($this->vocabulary->id(), 0, NULL, TRUE);
    $this->assertEquals($term1->id(), $terms[0]->id(), 'Term 1 was moved to back above term 2.');
    $this->assertEquals($term2->id(), $terms[1]->id(), 'Term 2 was moved to back below term 1.');
    $this->assertEquals($term3->id(), $terms[2]->id(), 'Term 3 is still below term 2.');
    $this->assertEquals([$term2->id()], $terms[2]->parents, 'Term 3 is still a child of term 2.');
  }

}
