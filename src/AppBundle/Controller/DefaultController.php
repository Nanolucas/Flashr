<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction() {
		\AppBundle\Classes\Phrase::set_config_data('database', $this->container->getParameter('database'));

		$template_data = [
			'page_title' => 'Flashr',
			'current_language' => \AppBundle\Classes\Language::get(),
			'phrase_data' => \AppBundle\Classes\Phrase::get_random(),
			'phrase2_data' => \AppBundle\Classes\Phrase::get_random(),
			'phrase3_data' => \AppBundle\Classes\Phrase::get_random(),
			'phrase4_data' => \AppBundle\Classes\Phrase::get_random(),
			'phrase5_data' => \AppBundle\Classes\Phrase::get_random(),
		];

        return $this->render('index.html.twig', $template_data);
    }

	/**
     * @Route("/{language}/phrase/{phrase}", name="phrase", requirements={"language": "[a-z]{2}"})
     */
    public function phraseAction($language, $phrase) {
		\AppBundle\Classes\Phrase::set_config_data('database', $this->container->getParameter('database'));

		$template_data = [
			'page_title' => "Flashr | {$phrase}",
			'current_language' => \AppBundle\Classes\Language::get(),
			'phrase_data' => \AppBundle\Classes\Phrase::get_by_translation($phrase, $language),
		];

		return $this->render('phrase.html.twig', $template_data);
    }

    /**
     * @Route("/{language}/test/", name="test", defaults={"language" = "default", "category" = "all"}, requirements={"language": "[a-z]{2}"})
     * @Route("/{language}/test/{category}", name="test_category", requirements={"language": "[a-z]{2}"})
     */
    public function testAction($language, $category) {
		\AppBundle\Classes\Phrase::set_config_data('database', $this->container->getParameter('database'));

		$template_data = [
			'page_title' => 'Flashr | Test Yoself',
			'current_language' => \AppBundle\Classes\Language::get(),
			'category' => $category,
		];

		$phrase_data = \AppBundle\Classes\Phrase::get_random($language);

		$alternative_answer_data = \AppBundle\Classes\Phrase::get_random_alternative_answers($phrase_data['phrase_id']);

		$type = rand(0,1);

		if ($type) {
			//PHRASE IN BASE LANGUAGE, CHOOSE IN NEW LANGUAGE
			$template_data['question']['phrase'] = $phrase_data['translation'];
			$template_data['question']['from_language_name'] = 'English';
			$template_data['question']['to_language_name'] = $phrase_data['language_name'];

			$type = 'phrase';
		} else {
			//PHRASE IN NEW LANGUAGE, CHOOSE IN BASE LANGUAGE
			$template_data['question']['phrase'] = $phrase_data['phrase'];
			$template_data['question']['from_language_name'] = $phrase_data['language_name'];
			$template_data['question']['to_language_name'] = 'English';

			$type = 'translation';
		}

		$template_data['question']['id'] = base_convert($phrase_data['phrase_id'], 10, 3);

		$template_data['answers'] = [
			[
				'id' => $phrase_data['phrase_id'],
				'word' => $phrase_data[$type],
			],
			[
				'id' => $alternative_answer_data[0]['phrase_id'],
				'word' => $alternative_answer_data[0][$type],
			],
			[
				'id' => $alternative_answer_data[1]['phrase_id'],
				'word' => $alternative_answer_data[1][$type],
			],
			[
				'id' => $alternative_answer_data[2]['phrase_id'],
				'word' => $alternative_answer_data[2][$type],
			],
		];

		shuffle($template_data['answers']);

        return $this->render('test.html.twig', $template_data);
    }

    /**
     * @Route("/{language}/test/question/", name="test_question", defaults={"language" = "default", "category" = "all"}, requirements={"language": "[a-z]{2}"})
     * @Route("/{language}/test/question/{category}", name="test_question_category", requirements={"language": "[a-z]{2}"})
     */
    public function testQuestionAction($language, $category) {
		\AppBundle\Classes\Phrase::set_config_data('database', $this->container->getParameter('database'));

		$template_data = [];

		$phrase_data = \AppBundle\Classes\Phrase::get_random($language);

		$alternative_answer_data = \AppBundle\Classes\Phrase::get_random_alternative_answers($phrase_data['phrase_id']);

		$type = rand(0,1);

		if ($type) {
			//PHRASE IN BASE LANGUAGE, CHOOSE IN NEW LANGUAGE
			$template_data['question']['phrase'] = $phrase_data['translation'];
			$template_data['question']['from_language_name'] = 'English';
			$template_data['question']['to_language_name'] = $phrase_data['language_name'];

			$type = 'phrase';
		} else {
			//PHRASE IN NEW LANGUAGE, CHOOSE IN BASE LANGUAGE
			$template_data['question']['phrase'] = $phrase_data['phrase'];
			$template_data['question']['from_language_name'] = $phrase_data['language_name'];
			$template_data['question']['to_language_name'] = 'English';

			$type = 'translation';
		}

		$template_data['question']['id'] = base_convert($phrase_data['phrase_id'], 10, 3);

		$template_data['answers'] = [
			[
				'id' => $phrase_data['phrase_id'],
				'word' => $phrase_data[$type],
			],
			[
				'id' => $alternative_answer_data[0]['phrase_id'],
				'word' => $alternative_answer_data[0][$type],
			],
			[
				'id' => $alternative_answer_data[1]['phrase_id'],
				'word' => $alternative_answer_data[1][$type],
			],
			[
				'id' => $alternative_answer_data[2]['phrase_id'],
				'word' => $alternative_answer_data[2][$type],
			],
		];

		shuffle($template_data['answers']);

        return $this->render('test_container.html.twig', $template_data);
    }

    /**
     * @Route("/{language}/test/answer/", name="test_answer", defaults={"language" = "default", "category" = "all"}, requirements={"language": "[a-z]{2}"})
     */
    public function testAnswerAction($language, $category) {
		\AppBundle\Classes\Phrase::set_config_data('database', $this->container->getParameter('database'));
		\AppBundle\Classes\Ajax::init();

		$question_id = base_convert($_POST['question'], 3, 10);

		if ($question_id == $_POST['answer']) {
			$phrase_data = \AppBundle\Classes\Phrase::get_by_id($question_id);
			$phrase_data['answer'] = 'ok';
        	echo \AppBundle\Classes\Ajax::success('Correct!', $phrase_data);
		} else {
        	echo \AppBundle\Classes\Ajax::error('Wrong :(', ['answer' => 'wrong']);
		}
    }

    /**
     * @Route("/{language}/set/", name="language_set", defaults={"language" = "default"}, requirements={"language": "[a-z]{2}"})
     */
    public function setLanguageAction($language) {
		\AppBundle\Classes\Language::set($language);

		header('Location: /');
		exit();
    }
}
