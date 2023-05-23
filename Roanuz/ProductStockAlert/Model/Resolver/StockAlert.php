<?php
namespace Roanuz\ProductStockAlert\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;


class StockAlert implements ResolverInterface
{
    protected $productRepository;

    protected $customerRepository;

    private $logger;

    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $sku = $args['sku'];
        $cutomerEmail = $args['cutomerEmail'];
        $status = false;
        $msg = "";
        // $customerData = $this->customerRepository->get($cutomerEmail);
        // $customerId = (int)$customerData->getId();
        try {
            $customerData = $this->customerRepository->get($cutomerEmail);
            $customerId = (int)$customerData->getId();
            $product = $this->productRepository->get($sku);
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore($storeId);
            $storeCode = $storeManager->getCode();
            $websiteId = $storeManager->getWebsiteId();
            $model = $objectManager->create(\Magento\ProductAlert\Model\Stock::class)
                ->setCustomerId($customerId)
                ->setProductId($product->getId())
                ->setWebsiteId($websiteId)
                ->setStoreId($storeId);
            $model->save();
            $status = true;
            $msg = "Alert subscription has been saved";
        } catch (NoSuchEntityException $noEntityException) {
            $msg = "There are not enough parameters.";
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        $response = array(
            'msg' => $msg,
            'status' => $status
        );
        return $response;
    }
}
