<?php

namespace MediaWiki\Extension\EquationNumbering;

use MediaWiki\Extension\EquationNumbering\EquationNumbering;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\ParserClearStateHook;
use MediaWiki\Parser\Parser;

class HookHandler implements ParserFirstCallInitHook, ParserClearStateHook {

    private $service;

    public function __construct( EquationNumbering $service ) {
        $this->service = $service;
    }

    /**
     * @param Parser $parser
     * @return void
     */
    public function onParserFirstCallInit( Parser $parser ): void {
        $parser->setFunctionHook( 'autoeq', [ $this->service, 'renderAutoNumEquation' ], Parser::SFH_OBJECT_ARGS );
        $parser->setFunctionHook( 'refeq', [ $this->service, 'renderRefEquation' ], Parser::SFH_OBJECT_ARGS );
    }

    /**
     * @param Parser $parser
     * @return void
     */
    public function onParserClearState( Parser $parser ): void {
        $this->service->clearState( $parser );
    }
}
