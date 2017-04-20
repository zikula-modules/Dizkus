<?php

/*
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 *
 * @see https://github.com/zikula-modules/Dizkus
 *
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

namespace Zikula\DizkusModule\Controller;

use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Hook\ValidationHook;
use Zikula\Core\Hook\ValidationProviders;
use Zikula\Core\Hook\ProcessHook;
use Zikula\Core\RouteUrl;
use Zikula\DizkusModule\Entity\RankEntity;
use Zikula\DizkusModule\Entity\ForumEntity;
use Zikula\DizkusModule\Form\Type\DizkusSettingsType;
use Zikula\DizkusModule\DizkusModuleInstaller;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route; // used in annotations - do not remove
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method; // used in annotations - do not remove

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("")
     *
     * the main administration function
     *
     * @return RedirectResponse
     */
    public function indexAction()
    {
        return new RedirectResponse($this->get('router')->generate('zikuladizkusmodule_admin_tree', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/order/{action}/{forum}", requirements={"action" = "moveUp|moveDown", "forum" = "^[1-9]\d*$"})
     * @Method("GET")
     *
     * Change forum order
     * Move up or down a forum in the tree
     *
     * @param string $action
     * @param ForumEntity $forum
     *
     * @return RedirectResponse
     *
     * @throws AccessDeniedException on Perm check failure
     */
    public function changeForumOrderAction($action, ForumEntity $forum)
    {
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }
        $em = $this->getDoctrine()->getManager();
        /** @var $repo \Gedmo\Tree\Entity\Repository\NestedTreeRepository */
        $repo = $em->getRepository('Zikula\DizkusModule\Entity\ForumEntity');
        if ($action == 'moveUp') {
            $repo->moveUp($forum, true);
        } else {
            $repo->moveDown($forum, true);
        }
        $em->flush();

        return new RedirectResponse($this->get('router')->generate('zikuladizkusmodule_admin_tree', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/prefs")
     *
     * @return Response
     *
     * @throws AccessDeniedException
     */
    public function preferencesAction(Request $request)
    {
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $adminsGroup = $this->getDoctrine()->getManager()->getRepository('Zikula\GroupsModule\Entity\GroupEntity')->find(2);
        $admins = ['-1' => $this->__('disabled')];
        foreach ($adminsGroup['users'] as $admin) {
            $admins[$admin->getUid()] = $admin->getUname();
        }

        $form = $this->createForm(new DizkusSettingsType(), $this->getVars(), ['admins' => $admins]);
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                //dump($form->getData());
                $this->setVars($form->getData());
                $this->addFlash('status', $this->__('Done! Updated configuration.'));
            }
            if ($form->get('restore')->isClicked()) {
                $this->setVars(DizkusModuleInstaller::getDefaultVars());
                $this->addFlash('status', $this->__('Done! Reset configuration to default values.'));
            }

            return $this->redirect($this->generateUrl('zikuladizkusmodule_admin_preferences'));
        }

        return $this->render('@ZikulaDizkusModule/Admin/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/sync")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     *
     * @throws AccessDeniedException
     */
    public function syncforumsAction(Request $request)
    {
        $showstatus = !$request->request->get('silent', 0);
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        if ($showstatus && $this->get('zikula_dizkus_module.synchronization_helper')->forums()) {
            $this->addFlash('status', $this->__('Done! Synchronized forums index.'));
        } else {
            $this->addFlash('error', $this->__('Error synchronizing forums index.'));
        }

        if ($showstatus && $this->get('zikula_dizkus_module.synchronization_helper')->topics()) {
            $this->addFlash('status', $this->__('Done! Synchronized topics.'));
        } else {
            $this->addFlash('error', $this->__('Error synchronizing topics.'));
        }

        if ($showstatus && $this->get('zikula_dizkus_module.synchronization_helper')->posters()) {
            $this->addFlash('status', $this->__('Done! Synchronized posts counter.'));
        } else {
            $this->addFlash('error', $this->__('Error synchronizing posts counter.'));
        }

        return new RedirectResponse($this->get('router')->generate('zikuladizkusmodule_admin_tree', [], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/ranks")
     *
     * ranks
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse
     *
     * @throws AccessDeniedException
     */
    public function ranksAction(Request $request)
    {
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }
        $submit = $request->request->get('submit', 2);
        $ranktype = (int) $request->query->get('ranktype', RankEntity::TYPE_POSTCOUNT);
        if ($submit == 2) {
            $ranks = $this->get('zikula_dizkus_module.rank_helper')->getAll(['ranktype' => RankEntity::TYPE_POSTCOUNT]);
            $template = 'honoraryranks';
            if ($ranktype == 0) {
                $template = 'ranks';
            }

            return $this->render("@ZikulaDizkusModule/Admin/$template.html.twig", [
                'ranks' => $ranks,
                'ranktype' => $ranktype,
                'rankimages' => $this->get('zikula_dizkus_module.rank_helper')->getAllRankImages(),
                'settings' => $this->getVars()
            ]);
        } else {
            $ranks = $request->request->filter('ranks', '', FILTER_SANITIZE_STRING);
            $this->get('zikula_dizkus_module.rank_helper')->save(['ranks' => $ranks]);
        }

        return new RedirectResponse($this->get('router')->generate('zikuladizkusmodule_admin_ranks', ['ranktype' => $ranktype], RouterInterface::ABSOLUTE_URL));
    }

    /**
     * @Route("/assignranks")
     *
     * ranks
     *
     * @return Response
     *
     * @throws AccessDeniedException
     */
    public function assignranksAction(Request $request)
    {
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $page = (int) $request->query->get('page', 1);
        $letter = $request->query->get('letter');

        if ($request->getMethod() == 'POST') {
            $letter = $request->request->get('letter');
            $page = (int) $request->request->get('page', 1);

            $setrank = $request->request->get('setrank');

            $this->get('zikula_dizkus_module.rank_helper')->assign(['setrank' => $setrank]);

            return new RedirectResponse($this->get('router')->generate('zikuladizkusmodule_admin_assignranks', ['page' => $page, 'letter' => $letter], RouterInterface::ABSOLUTE_URL));
        }

        $letter = (empty($letter) || strlen($letter) != 1) ? '*' : $letter;
        $perpage = 20;
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('u')
        ->from('Zikula\UsersModule\Entity\UserEntity', 'u')
        ->orderBy('u.uname', 'ASC');
        if ($letter != '*') {
            $qb->andWhere('u.uname LIKE :letter')
            ->setParameter('letter', strtolower($letter) . '%');
        }
        $query = $qb->getQuery();
        $query->setFirstResult(($page - 1) * $perpage)
        ->setMaxResults($perpage);

        // Paginator
        $allusers = new Paginator($query);
        $count = $allusers->count();

        // recreate the array of users as ForumUserEntities
        $userArray = [];
        /** @var $user \Zikula\UsersModule\Entity\UserEntity */
        foreach ($allusers as $user) {
            $managedForumUser = $this->get('zikula_dizkus_module.forum_user_manager')->getManager($user->getUid(), false);
            $forumUser = $managedForumUser->get();
            if (isset($forumUser)) {
                $userArray[$user->getUid()] = $forumUser;
            } else {
                $count--;
            }
        }

        $ranks = $this->get('zikula_dizkus_module.rank_helper')->getAll(['ranktype' => RankEntity::TYPE_HONORARY]);

        return $this->render('@ZikulaDizkusModule/Admin/assignranks.html.twig', [
            'ranks' => $ranks,
            'rankimages' => $this->get('zikula_dizkus_module.rank_helper')->getAllRankImages(),
            'allusers' => $userArray,
            'letter' => $letter,
            'page' => $page,
            'perpage' => $perpage,
            'usercount' => $count
        ]);
    }

    /**
     * @Route("/tree")
     *
     * Show the forum tree
     *
     * @return Response
     *
     * @throws AccessDeniedException
     */
    public function treeAction()
    {
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        return $this->render('@ZikulaDizkusModule/Admin/tree.html.twig', [
            'tree' => $this->getDoctrine()->getManager()->getRepository('Zikula\DizkusModule\Entity\ForumEntity')->childrenHierarchy(null, false)
        ]);
    }

    /**
     * @Route("/modify/{id}")
     *
     * @return Response
     */
    public function modifyForumAction(Request $request, $id = null)
    {
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        // disallow editing of root forum
        if ($id == 1) {
            $this->addFlash('error', $this->__("Editing of root forum is disallowed", 403));

            return new RedirectResponse($this->get('router')->generate('zikuladizkusmodule_admin_tree', [], RouterInterface::ABSOLUTE_URL));
        }

        $managedForum = $this->get('zikula_dizkus_module.forum_manager')->getManager($id);

        // count only in this forum or subforums as well?
        $topiccount = $this->get('zikula_dizkus_module.count_helper')->getForumTopicsCount($managedForum->getId());
        $postcount = $this->get('zikula_dizkus_module.count_helper')->getForumPostsCount($managedForum->getId());

        $forum = $managedForum->get();

        $form = $this->createForm('zikuladizkusmodule_admin_modify_forum', $forum , []);

        $em = $this->getDoctrine()->getManager();
        $form->handleRequest($request);

        // check hooked modules for validation
        $hook = new ValidationHook(new ValidationProviders());
        $hookvalidators = $this->get('hook_dispatcher')->dispatch('dizkus.ui_hooks.forum.validate_edit', $hook)->getValidators();

        if ($form->isValid() && !$hookvalidators->hasErrors()) {

            $em->persist($forum);
            $em->flush();

            // notify hooks
            $hookUrl = RouteUrl::createFromRoute('zikuladizkusmodule_user_viewforum', ['forum' => $forum->getForum_id()]);
            $this->get('hook_dispatcher')->dispatch('dizkus.ui_hooks.forum.process_edit', new ProcessHook($forum->getForum_id(), $hookUrl));

            if ($id) {
                $this->addFlash('status', $this->__('Forum successfully updated.'));
            } else {
                $this->addFlash('status', $this->__('Forum successfully created.'));
            }
        }

        return $this->render('@ZikulaDizkusModule/Admin/modifyforum.html.twig', [
            'topiccount' => $topiccount,
            'postcount' => $postcount,
            'forum' => $forum,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}")
     *
     * @return Response
     */
    public function deleteforumAction(Request $request, $id = null)
    {
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        if ($id) {
            $forum = $this->getDoctrine()->getManager()->find('Zikula\DizkusModule\Entity\ForumEntity', $id);
            if ($forum) {
                //nothing to do here? @todo rearange this if
            } else {
                $this->addFlash('error', $this->__f('Forum with id %s not found', ['%s' => $id]), 403);

                return new RedirectResponse($this->get('router')->generate('zikuladizkusmodule_admin_tree', [], RouterInterface::ABSOLUTE_URL));
            }
        } else {
            $this->addFlash('error', $this->__('No forum id'), 403);

            return new RedirectResponse($this->get('router')->generate('zikuladizkusmodule_admin_tree', [], RouterInterface::ABSOLUTE_URL));
        }

        $forumRoot = $this->getDoctrine()->getManager()->getRepository('Zikula\DizkusModule\Entity\ForumEntity')->findOneBy(['name' => ForumEntity::ROOTNAME]);
        $destinations = $this->getDoctrine()->getManager()->getRepository('Zikula\DizkusModule\Entity\ForumEntity')->getChildren($forumRoot);

        // @todo move to form handler
        $form = $this->createFormBuilder([])
        ->add('action', 'choice', [
            'choices' => ['0' => $this->__('Remove them'),
                '1' => $this->__('Move them to a new parent forum')],
            'multiple' => false,
            'expanded' => false,
            'required' => true])
        ->add('destination', 'choice', [
            'choices' => $destinations,
            'choice_value' => function ($destination) {
                //for some reason last element is null @FIXME
                return $destination === null ? null : $destination->getForum_id();
            },
            'choice_label' => function ($destination) use ($forum) {
                $isChild = $destination->getLft() > $forum->getLft() && $destination->getRgt() < $forum->getRgt() ? ' (' . $this->__("is child forum") . ')' : '';
                $current = $destination->getForum_id() === $forum->getForum_id() ? ' (' . $this->__("current") . ')' : '';
                $locked = $destination->isLocked() ? ' (' . $this->__("is locked") . ')' : '';

                return str_repeat("--", $destination->getLvl()) . $destination->getName() . $current . $locked . $isChild;
            },
            'choice_attr' => function ($destination) use ($forum) {
                $isChild = $destination->getLft() > $forum->getLft() && $destination->getRgt() < $forum->getRgt() ? true : false;

                return $destination->getForum_id() === $forum->getForum_id() || $destination->isLocked() || $isChild ? ['disabled' => 'disabled'] : [];
            },
            'choices_as_values' => true,
            'multiple' => false,
            'expanded' => false,
            'required' => true])
        ->add('remove', 'submit')
        ->getForm();

        $form->handleRequest($request);

        // check hooked modules for validation
        $hook = new ValidationHook(new ValidationProviders());
        $hookvalidators = $this->get('hook_dispatcher')->dispatch('dizkus.ui_hooks.forum.validate_delete', $hook)->getValidators();

        if ($form->isValid() && !$hookvalidators->hasErrors()) {
            $data = $form->getData();
            if ($data['action'] == 1) {
                // get the child forums and move them
                $children = $forum->getChildren();
                foreach ($children as $child) {
                    $child->setParent($data['destination']);
                }
                $forum->removeChildren();

                // get child topics and move them
                $topics = $forum->getTopics();
                foreach ($topics as $topic) {
                    $topic->setForum($data['destination']);
                    $forum->getTopics()->removeElement($topic);
                }
                $this->getDoctrine()->getManager()->flush();
            }
            // remove the forum
            $this->getDoctrine()->getManager()->remove($forum);
            $this->getDoctrine()->getManager()->flush();

            $this->get('hook_dispatcher')->dispatch('dizkus.ui_hooks.forum.process_delete', new ProcessHook($forum->getForum_id()));

            if (isset($data['destination'])) {
                // sync last post in destination
                $this->get('zikula_dizkus_module.synchronization_helper')->forumLastPost($data['destination']);
            }

            // repair the tree
            $this->getDoctrine()->getManager()->getRepository('Zikula\DizkusModule\Entity\ForumEntity')->recover();
            $this->getDoctrine()->getManager()->clear();

            // resync all forums, topics & posters
            $this->get('zikula_dizkus_module.synchronization_helper')->all();
        }

        return $this->render('@ZikulaDizkusModule/Admin/deleteforum.html.twig', [
            'forum' => $forum,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/subscriptions/{uid}", options={"expose"=true})
     *
     * @return Response
     */
    public function manageSubscriptionsAction(Request $request, $uid = null)
    {
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        if ($request->isMethod('POST') && $request->request->get('username', false)){
            $managedForumUser = $this->get('zikula_dizkus_module.forum_user_manager')->getManagedByUserName($request->request->get('username'), false);
        }else{
            $managedForumUser = $this->get('zikula_dizkus_module.forum_user_manager')->getManager($uid, false);
        }

        if ($request->isMethod('POST')) {
            if ($managedForumUser->exists()){
                $forumSub = $request->request->get('forumsubscriptions', []);
                foreach ($forumSub as $id => $selected) {
                    if ($selected) {
                        $managedForumUser->unsubscribeForum($id);
                    }
                }

                $topicSub = $request->request->get('topicsubscriptions', []);
                foreach ($topicSub as $id => $selected) {
                    if ($selected) {
                        $managedForumUser->unsubscribeFromTopic($id);
                    }
                }
            }
        }

        return $this->render('@ZikulaDizkusModule/Admin/managesubscriptions.html.twig', [
            'managedForumUser' => $managedForumUser
        ]);
    }

    /**
     * @Route("/hookconfig/{moduleName}")
     * @Method("GET")
     *
     * configure dizkus hook options for given module
     *
     * @param $moduleName
     * @return Response
     */
    public function hookConfigAction($moduleName)
    {
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $hookconfig = $this->get('zikula_extensions_module.api.variable')->get($moduleName, 'dizkushookconfig');
        // looks like module should always exist it would not be hooked
        $module = $this->get('zikula_extensions_module.api.extension')->getModuleInstanceOrNull($moduleName);
        $moduleHookCapable = $this->get('zikula_extensions_module.api.capability')->isCapable($moduleName, 'hook_subscriber');

        $moduleHookContainerClass = $moduleHookCapable['class'];
        $moduleHookContainer = new $moduleHookContainerClass($this->get('translator.default'));
        $bindingsBetweenOwners = $this->get('hook_dispatcher')->getBindingsBetweenOwners($moduleName, $this->name);
        foreach ($bindingsBetweenOwners as $k => $binding) {
            $areaname = $this->getDoctrine()->getManager()->getRepository('Zikula\Bundle\HookBundle\Dispatcher\Storage\Doctrine\Entity\HookAreaEntity')->find($binding['sareaid'])->getAreaname();
            $bindingsBetweenOwners[$k]['areaname'] = $areaname;
            $bindingsBetweenOwners[$k]['areatitle'] = $moduleHookContainer->getHookSubscriberBundle($areaname)->getTitle();
        }

        return $this->render('@ZikulaDizkusModule/Hook/modifyconfig.html.twig', [
            'areas' => $bindingsBetweenOwners,
            'dizkushookconfig' => $hookconfig,
            'activeModule' => $module,
            'forums' => $this->get('zikula_dizkus_module.forum_manager')->getParents(null, true),
        ]);
    }

    /**
     * @Route("/hookconfig")
     * @Method("POST")
     *
     * process dizkus hook options for a given module
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function hookConfigProcessAction(Request $request)
    {
        $hookdata = $request->request->get('dizkus', []);
        $moduleName = $request->request->get('activeModule');
        if (!$this->hasPermission($this->name . '::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        foreach ($hookdata as $area => $data) {
            if (!isset($data['forum']) || empty($data['forum'])) {
                $this->addFlash('error', $this->__f('Error: No forum selected for area \'%s\'', $area));
                $hookdata[$area]['forum'] = null;
            }
        }
        // ModVar: dizkushookconfig => array('areaid' => array('forum' => value))
        $this->get('zikula_extensions_module.api.variable')->set($moduleName, 'dizkushookconfig', $hookdata);
        $this->addFlash('status', $this->__('Dizkus: Hook option settings updated.'));

        //return new RedirectResponse(System::normalizeUrl(ModUtil::url($moduleName, 'admin', 'index')));
        return new RedirectResponse($this->get('router')->generate('zikuladizkusmodule_admin_hookconfig', ['moduleName' => $moduleName], RouterInterface::ABSOLUTE_URL));
    }
}
