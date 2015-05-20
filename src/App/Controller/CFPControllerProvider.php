<?php

namespace App\Controller;

use App\Form\CFPType;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Constraints;

/**
 * Class CFPControllerProvider.
 */
class CFPControllerProvider implements ControllerProviderInterface
{

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->match('/', function (Application $app, Request $request) {
            $flashBag = $app['session']->getFlashBag();
            $translator = $app['translator'];

            $form = $this->createForm($app['form.factory'], $translator);

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $data['created'] = new \MongoDate();

                $app['mongodb']
                    ->selectDatabase('phpday')
                    ->selectCollection('cfp')
                    ->insert($data);

                $flashBag->add('cfp_messages', [
                    'type' => 'success',
                    'msg' => $translator->trans('cfp.success_message'),
                ]);

                return new RedirectResponse($app['url_generator']->generate('cfp'));
            }

            return $app['twig']->render('cfp.html.twig', [
                'cfp_form' => $form->createView(),
                'messages' => $flashBag->get('cfp_messages'),
            ]);
        })->method('GET|POST')->bind('cfp');

        return $controllers;
    }

    /**
     * @param FormFactoryInterface $formFactory
     * @param TranslatorInterface  $translator
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createForm(FormFactoryInterface $formFactory, TranslatorInterface $translator)
    {
        return $formFactory->create(new CFPType(), null, ['translator' => $translator]);
    }
}
