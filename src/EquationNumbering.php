<?php

namespace MediaWiki\Extension\EquationNumbering;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use WeakMap;

class EquationNumbering {

    /**
     * @var WeakMap|null
     */
    private $parserStates = null;

    /**
     * @return WeakMap
     */
    private function getParserStates(): WeakMap {
        if ( $this->parserStates === null ) {
            $this->parserStates = new WeakMap();
        }
        return $this->parserStates;
    }

    /**
     * @param Parser $parser
     */
    public function clearState( Parser $parser ): void {
        $states = $this->getParserStates();
        if ( isset( $states[$parser] ) ) {
            unset( $states[$parser] );
        }
    }

    /**
     * @param Parser $parser
     * @param PPFrame $frame
     * @param array $args
     * @return array
     */
    public function renderAutoNumEquation( Parser $parser, PPFrame $frame, array $args ): array {
        $states = $this->getParserStates();

        if ( !isset( $states[$parser] ) ) {
            $states[$parser] = [
                'counter' => 0,
                'labels' => []
            ];
        }

        $currentState = $states[$parser];

        $expressionWikitext = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
        $label = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';

        $currentState['counter']++;
        $currentCount = $currentState['counter'];

        $parsedExpression = $parser->recursiveTagParse( $expressionWikitext, $frame );

        $lookupLabel = $label;
        if ( $lookupLabel === '' ) {
            $lookupLabel = 'eqnum-' . $currentCount;
        }

        $sanitizedId = preg_replace( '/[^a-zA-Z0-9\-_:.]/', '_', $lookupLabel );

        $currentState['labels'][$lookupLabel] = [
            'count' => $currentCount,
            'id' => $sanitizedId
        ];

        $states[$parser] = $currentState;

        $output = '<div class="equation-container" id="' . htmlspecialchars( $sanitizedId ) . '">';
        $output .= '<span class="equation-expr">' . $parsedExpression . '</span>';
        $output .= '<span class="equation-number">(' . $currentCount . ')</span>';
        $output .= '</div>';

        return [ 'text' => $output, 'noparse' => true, 'isHTML' => true ];
    }

    /**
     * @param Parser $parser
     * @param PPFrame $frame
     * @param array $args
     * @return array
     */
    public function renderRefEquation( Parser $parser, PPFrame $frame, array $args ): array {
        $states = $this->getParserStates();

        $label = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
        $output = '';

        if ( $label !== '' && isset( $states[$parser] ) && isset( $states[$parser]['labels'][$label] ) ) {
            $data = $states[$parser]['labels'][$label];
            $id = htmlspecialchars( $data['id'] );
            $count = htmlspecialchars( (string)$data['count'] );

            $output = '<a class="equation-ref" href="#' . $id . '">(' . $count . ')</a>';
        } else {
            $output = '<span class="error" style="color: red; font-weight: bold;">Equation reference not found: ' . htmlspecialchars( $label ) . '</span>';
        }

        return [ 'text' => $output, 'noparse' => true, 'isHTML' => true ];
    }
}
