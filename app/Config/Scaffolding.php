<?php

declare(strict_types=1);

namespace Config;

use dcardenasl\Ci4ApiScaffolding\Config\BaseScaffoldingConfig;
use dcardenasl\Ci4ApiScaffolding\Config\ScaffoldingConfig;

class Scaffolding extends BaseScaffoldingConfig
{
    public function build(): ScaffoldingConfig
    {
        $defaults = ScaffoldingConfig::defaults();

        return new ScaffoldingConfig(
            controllerBaseClass: $defaults->controllerBaseClass,
            serviceBaseClass: $defaults->serviceBaseClass,
            serviceContractInterface: $defaults->serviceContractInterface,
            modelBaseClass: $defaults->modelBaseClass,
            entityBaseClass: $defaults->entityBaseClass,
            migrationBaseClass: $defaults->migrationBaseClass,
            requestDtoBaseClass: $defaults->requestDtoBaseClass,
            responseDtoInterface: $defaults->responseDtoInterface,
            repositoryInterface: $defaults->repositoryInterface,
            responseMapperInterface: $defaults->responseMapperInterface,
            repositoryImplementation: $defaults->repositoryImplementation,
            responseMapperImplementation: $defaults->responseMapperImplementation,
            servicesFactoryClass: $defaults->servicesFactoryClass,
            paths: $defaults->paths,
            // Override: the website builder app delegates auth to the hub via DomainAuthFilter.
            // Resource-level gates are emitted by the scaffolder.
            protectedRouteFilters: ['domainauth', 'throttle'],
            appNamespace: $defaults->appNamespace,
            openApiTagPrefix: $defaults->openApiTagPrefix,
            conditionalControllerTraits: $defaults->conditionalControllerTraits,
            permissionCodePrefix: 'cms',
        );
    }
}
