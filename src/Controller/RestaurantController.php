<?php

namespace App\Controller;

use App\Entity\Rates;
use App\Entity\Restaurant;
use OpenApi\Attributes as OA;
use JMS\Serializer\SerializerInterface;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Repository\RestaurantOwnerRepository;
use OpenApi\Attributes\RequestBody;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\VarExporter\Internal\Values;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class RestaurantController extends AbstractController
{
    #[Route('/restaurant', name: 'app_restaurant')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/RestaurantController.php',
        ]);
    }

    /**
     * Get a list of restaurants.
     */
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: Restaurant::class))
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'No restaurants found',
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'The page number',
        required: false,
        example: 1,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'The number of items per page',
        required: false,
        example: 10,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'Restaurants')]
    #[Security(name: 'Bearer')]
    #[Route('/api/restaurant', name: 'restaurant.getAll', methods: ['GET'])]
    public function getRestaurant(RestaurantRepository $repository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 5);
        $limit = $limit > 20 ? 20 : $limit;

        $idCache = 'getRestaurant';
        $data = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer, $page, $limit) {
            echo 'Cache saved ?????????????';
            $item->tag('restaurantCache');
            $restaurant = $repository->findWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['showRestaurant']);
            return $serializer->serialize($restaurant, 'json', $context);
        });
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    /**
     * Get details of a restaurant.
     */
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Restaurant::class)
    )]
    #[OA\Response(
        response: 404,
        description: 'Restaurant not found',
    )]
    #[OA\Tag(name: 'Restaurants')]
    #[Security(name: 'Bearer')]
    #[Route('/api/restaurant/{idRestaurant}', name: 'restaurant.getOne', methods: ['GET'])]
    #[ParamConverter('restaurant', options: ['id' => 'idRestaurant'])]
    public function getOneRestaurant(Restaurant $restaurant, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = 'getOneRestaurant' . $restaurant->getId();
        $data = $cache->get($idCache, function (ItemInterface $item) use ($restaurant, $serializer) {
            echo 'Cache saved ?????????????';
            $item->tag('restaurantCache');
            $context = SerializationContext::create()->setGroups(['showRestaurant']);
            return $serializer->serialize($restaurant, 'json', $context);
        });
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    /**
     * Update a restaurant.
     */
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Restaurant::class)
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request',
    )]
    #[OA\RequestBody(
        request: 'RestaurantData',
        description: 'You have to fill all the fields',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            ref: '#/components/schemas/RestaurantData'
        )
    )]
    #[OA\Tag(name: 'Restaurants')]
    #[Security(name: 'Bearer')]
    #[Route('/api/restaurant/{idRestaurant}', name: 'restaurant.put', methods: ['PUT'])]
    #[ParamConverter('restaurant', options: ['id' => 'idRestaurant'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour effectuer cette action')]
    public function updateRestaurant(Request $request, EntityManagerInterface $manager, SerializerInterface $serializer, Restaurant $restaurant, TagAwareCacheInterface $cache): JsonResponse
    {
        $data = $serializer->deserialize(
            $request->getContent(),
            Restaurant::class,
            'json'
        );

        $restaurant->setRestaurantName($data->getRestaurantName() ? $data->getRestaurantName() : $restaurant->getRestaurantName());
        $restaurant->setRestaurantPhone($data->getRestaurantPhone() ? $data->getRestaurantPhone() : $restaurant->getRestaurantPhone());
        $restaurant->setRestaurantDescription($data->getRestaurantDescription() ? $data->getRestaurantDescription() : $restaurant->getRestaurantDescription());
        $restaurant->setRestaurantLatitude($data->getRestaurantLatitude() ? $data->getRestaurantLatitude() : $restaurant->getRestaurantLatitude());
        $restaurant->setRestaurantLongitude($data->getRestaurantLongitude() ? $data->getRestaurantLongitude() : $restaurant->getRestaurantLongitude());

        $cache->invalidateTags(['restaurantCache']);
        $context = SerializationContext::create()->setGroups(['showRestaurant']);
        $response = $serializer->serialize($restaurant, 'json', $context);
        $manager->persist($restaurant);
        $manager->flush();
        return new JsonResponse($response, Response::HTTP_OK, [], true);
    }

    /**
     * Soft delete for a restaurant.
     */
    #[OA\Tag(name: 'Restaurants')]
    #[Security(name: 'Bearer')]
    #[OA\Response(
        response: 200,
        description: 'Restaurant supprim??',
    )]
    #[OA\Response(
        response: 404,
        description: 'Restaurant non trouv??',
    )]
    #[Route('/api/restaurant/{idRestaurant}', name: 'restaurant.delete', methods: ['DELETE'])]
    #[ParamConverter('restaurant', options: ['id' => 'idRestaurant'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour effectuer cette action')]
    public function deleteRestaurant(Restaurant $restaurant, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(['restaurantCache']);
        $restaurant->setStatus("false");
        $entityManager->flush();
        return new JsonResponse('Restaurant supprim??', Response::HTTP_OK, [], true);
    }

    /**
     * Create a restaurant.
     */
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Restaurant::class)
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request',
    )]
    #[OA\RequestBody(
        request: 'RestaurantData',
        description: 'You have to fill all the fields',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            ref: '#/components/schemas/RestaurantData'
        )
    )]
    #[OA\Tag(name: 'Restaurants')]
    #[Security(name: 'Bearer')]
    #[Route('/api/restaurant', name: 'restaurant.create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour effectuer cette action')]
    public function createRestaurant(ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $entityManager, Request $request, restaurantOwnerRepository $usersRepository, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(['restaurantCache']);
        $restaurant = $serializer->deserialize($request->getContent(), Restaurant::class, 'json');
        $restaurant->setStatus("true");

        $content = $request->toArray();
        $idOwner = $content['idOwner'];
        $owner = $usersRepository->find($idOwner);

        $errors = $validator->validate($restaurant);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $restaurant->setRestaurantOwner($owner);

        $entityManager->persist($restaurant);
        $entityManager->flush();
        $context = SerializationContext::create()->setGroups(['showRestaurant']);
        $jsonRestaurant = $serializer->serialize($restaurant, 'json', $context);
        return new JsonResponse($jsonRestaurant, Response::HTTP_CREATED, [], true);
    }

    /**
     * Get a list of restaurants based on latitude and longitude.
     */
    #[OA\Tag(name: 'Restaurants')]
    #[Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'The page number',
        required: false,
        example: 1,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'The number of items per page',
        required: false,
        example: 10,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'latitude',
        in: 'query',
        description: 'The latitude of the user',
        required: true,
        example: 48.856614,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'longitude',
        in: 'query',
        description: 'The longitude of the user',
        required: true,
        example: 2.3522219,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'distance',
        in: 'query',
        description: 'The maximum distance between the user and the restaurant (in KM)',
        required: true,
        example: 20,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: Restaurant::class))
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'No restaurants found',
    )]
    #[Route('/api/closest/restaurant/', name: 'restaurant.closest', methods: ['GET'])]
    public function getClosestRestaurant(SerializerInterface $serializer, Request $request, RestaurantRepository $restaurantRepository, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 5);
        $limit = $limit > 20 ? 20 : $limit;

        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');
        $distance = $request->query->get('distance');

        $cacheId = 'getClosestRestaurant' . $latitude . $longitude . $distance;
        $data = $cache->get($cacheId, function (ItemInterface $item) use ($restaurantRepository, $serializer, $page, $limit, $latitude, $longitude, $distance) {
            echo 'Cache saved ?????????????';
            $item->tag('restaurantCache');
            $restaurant = $restaurantRepository->findClosestRestaurant($latitude, $longitude, $distance, $page, $limit);
            $context = SerializationContext::create()->setGroups(['showRestaurant']);
            return $serializer->serialize($restaurant, 'json', $context);
        });
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[OA\Tag(name: 'Restaurants')]
    #[Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'rate',
        in: 'query',
        description: 'number of stars',
        required: true,
        example: 4,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Restaurant::class)
    )]
    #[OA\Response(
        response: 404,
        description: 'No restaurants found',
    )]
    #[Route('/api/restaurant/{idRestaurant}/rate', name: 'restaurant.rate', methods: ['POST'])]
    #[ParamConverter('restaurant', options: ['id' => 'idRestaurant'])]
    public function rateRestaurant(Restaurant $restaurant, Request $request, EntityManagerInterface $manager, SerializerInterface $serializer, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $rate = new Rates();
        $rate->setUser($this->getUser());
        $requestRate = $request->query->get('rate');
        $requestRate > 5 ? $requestRate = 5 : $requestRate;
        $requestRate < 1 ? $requestRate = 1 : $requestRate;
        
        $rate->setStarsNumber($request->query->get('rate'));

        $restaurant->addRate($rate);

        $cache->invalidateTags(['restaurantCache']);
        $context = SerializationContext::create()->setGroups(['showRestaurant']);
        $response = $serializer->serialize($restaurant, 'json', $context);
        $manager->persist($restaurant);
        $manager->persist($rate);
        $manager->flush();
        return new JsonResponse($response, Response::HTTP_OK, [], true);
    }
}
