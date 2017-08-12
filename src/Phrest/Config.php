<?php
namespace Phrest;

abstract class Config
{
    const SWAGGER_SCAN_DIRECTORY = 'phrest_swagger_scan_directory';

    const ENABLE_CACHE = 'phrest_cache_enabled';
    const CACHE_DIRECTORY = 'phrest_cache_directory';

    const MONOLOG_HANDLER = 'phrest_monolog_handler';
    const MONOLOG_PROCESSOR = 'phrest_monolog_processor';

    const ROUTES = 'phrest_routes';
    const DEPENDENCIES = 'phrest_dependencies';
    const ERROR_CODES = 'phrest_error_codes';

    const LOGGER = 'phrest_logger';
    const SWAGGER = 'phrest_swagger';

    const PRE_ROUTING_MIDDLEWARE = 'phrest_pre_routing_middleware';
    const PRE_DISPATCHING_MIDDLEWARE = 'phrest_pre_dispatching_middleware';
    const POST_DISPATCHING_MIDDLEWARE = 'phrest_post_dispatching_middleware';

    const ACTION_SWAGGER = 'phrest_action_swagger';
    const ACTION_ERROR_CODES = 'phrest_action_error_codes';

    const HATEOAS_RESPONSE_GENERATOR = 'phrest_hateoas_response_generator';
    const REQUEST_SWAGGER_VALIDATOR = 'phrest_request_swagger_validator';
}