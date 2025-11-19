<?php
/*
 * EquationNumbering
 * Adds two new parser functions to enable sequential numeration of mathematical expressions
 *
 * @file
 * @author Daniel Gracia Garallar
 */

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

class EquationNumbering {

    /**
     * Accounts for state data for every Parser instance.
     * * @var WeakMap|null
     */
    private static $parserStates = null;

    /**
     * Gets/inits WeakMap state.
     * @return WeakMap
     */
    private static function getParserStates(): WeakMap {
        if ( self::$parserStates === null ) {
            self::$parserStates = new WeakMap();
        }
        return self::$parserStates;
    }

    /**
     * Registers our parser functions.
     * @param Parser $parser
     */
    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setFunctionHook( 'autoeq', [ self::class, 'renderAutoNumEquation' ], Parser::SFH_OBJECT_ARGS );
        $parser->setFunctionHook( 'refeq', [ self::class, 'renderRefEquation' ], Parser::SFH_OBJECT_ARGS );
    }

    /**
     * Clears our extension's state data from the parser.
     * @param Parser $parser
     */
    public static function onParserClearState( Parser $parser ) {
        // Eliminamos la entrada asociada a este parser del WeakMap
        $states = self::getParserStates();
        if ( isset( $states[$parser] ) ) {
            unset( $states[$parser] );
        }
    }

    /**
     * Renders the auto-numbered equation, e.g. {{#autoeq: <math>myexpression</math> | mylabel }}
     *
     * @param Parser $parser
     * @param PPFrame $frame The frame object for template processing.
     * @param array $args The arguments passed to the parser function.
     * @return array
     */
    public static function renderAutoNumEquation( Parser $parser, PPFrame $frame, array $args ) {
        $states = self::getParserStates();

        // Initialize counter and labels for this specific parser instance if not present
        if ( !isset( $states[$parser] ) ) {
            $states[$parser] = [
                'counter' => 0,
                'labels' => []
            ];
        }

        // Retrieve current state (arrays are pass-by-value, so we retrieve, modify, and save back)
        $currentState = $states[$parser];

        // Get the full wikitext of the expression and the label
        $expressionWikitext = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
        $label = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';

        // Increment the equation counter for the current page
        $currentState['counter']++;
        $currentCount = $currentState['counter'];

        $parsedExpression = $parser->recursiveTagParse( $expressionWikitext, $frame );

        $lookupLabel = $label;
        if ( $lookupLabel === '' ) {
            $lookupLabel = 'eqnum-' . $currentCount;
        }
    
        // Sanitize the label for use as a valid HTML id.
        $sanitizedId = preg_replace( '/[^a-zA-Z0-9\-_:.]/', '_', $lookupLabel );
    
        // Always store the label information.
        $currentState['labels'][$lookupLabel] = [
            'count' => $currentCount,
            'id' => $sanitizedId
        ];

        // State back to WeakKMap
        $states[$parser] = $currentState;

        // Assemble the final HTML output
        $output = '<div class="equation-container" id="' . htmlspecialchars( $sanitizedId ) . '">';
        $output .= '<span class="equation-expr">' . $parsedExpression . '</span>';
        $output .= '<span class="equation-number">(' . $currentCount . ')</span>';
        $output .= '</div>';

        // Return the result as raw HTML that should not be parsed further
        return [ 'text' => $output, 'noparse' => true, 'isHTML' => true ];
    }

    /**
     * Renders a reference to an equation. {{#refeq: mylabel }}
     *
     * @param Parser $parser
     * @param PPFrame $frame The frame object for template processing.
     * @param array $args The arguments passed to the parser function.
     * @return array
     */
    public static function renderRefEquation( Parser $parser, PPFrame $frame, array $args ) {
        $states = self::getParserStates();
        
        $label = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
        $output = '';

        // Check if we have state for this parser AND if the label exists within that state
        if ( $label !== '' && isset( $states[$parser] ) && isset( $states[$parser]['labels'][$label] ) ) {
            $data = $states[$parser]['labels'][$label];
            $id = htmlspecialchars( $data['id'] );
            $count = htmlspecialchars( (string)$data['count'] ); // Ensure string for output

            $output = '<a class="equation-ref" href="#' . $id . '">(' . $count . ')</a>';
        } else {
            $output = '<span class="error" style="color: red; font-weight: bold;">Equation reference not found: ' . htmlspecialchars( $label ) . '</span>';
        }

        return [ 'text' => $output, 'noparse' => true, 'isHTML' => true ];
    }
}
