<?php
/*
 *  EquationNumbering
 *  Adds two new parser functions to enable sequential numeration of mathematical expressions
 *
 * @file
 * @author Daniel Gracia Garallar
 */

class EquationNumbering {

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
        unset( $parser->mEquationNumberingCounter );
        unset( $parser->mEquationNumberingLabels );
    }

    /**
     * Renders the auto-numbered equation, e.g. {{#autoeq: <math>myexpression</math> | mylabel }}
     *
     * @param Parser $parser
     * @param \PPFrame $frame The frame object for template processing.
     * @param array $args The arguments passed to the parser function.
     * @return array
     */
    public static function renderAutoNumEquation( Parser $parser, \PPFrame $frame, array $args ) {
        // Initialize counter and labels on the parser object if they don't exist
        if ( !isset( $parser->mEquationNumberingCounter ) ) {
            $parser->mEquationNumberingCounter = 0;
            $parser->mEquationNumberingLabels = [];
        }

        // Get the full wikitext of the expression and the label
        $expressionWikitext = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
        $label = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';

        // Increment the equation counter for the current page
        $parser->mEquationNumberingCounter++;
        $currentCount = $parser->mEquationNumberingCounter;

        $parsedExpression = $parser->recursiveTagParse( $expressionWikitext, $frame );

        $lookupLabel = $label;
        if ( $lookupLabel === '' ) {
            $lookupLabel = 'eqnum-' . $currentCount;
        }
    
        // Sanitize the label for use as a valid HTML id.
        $sanitizedId = preg_replace( '/[^a-zA-Z0-9\-_:.]/', '_', $lookupLabel );
    
        // Always store the label information.
        $parser->mEquationNumberingLabels[$lookupLabel] = [
            'count' => $currentCount,
            'id' => $sanitizedId
        ];

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
     * @param \PPFrame $frame The frame object for template processing.
     * @param array $args The arguments passed to the parser function.
     * @return array
     */
    public static function renderRefEquation( Parser $parser, \PPFrame $frame, array $args ) {
        $label = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
        $output = '';

        if ( $label !== '' && isset( $parser->mEquationNumberingLabels ) && isset( $parser->mEquationNumberingLabels[$label] ) ) {
            $data = $parser->mEquationNumberingLabels[$label];
            $id = htmlspecialchars( $data['id'] );
            $count = htmlspecialchars( $data['count'] ); // It's an int, but sanitize for safety

            $output = '<a class="equation-ref" href="#' . $id . '">(' . $count . ')</a>';
        } else {
            $output = '<span class="error" style="color: red; font-weight: bold;">Equation reference not found: ' . htmlspecialchars( $label ) . '</span>';
        }

        return [ 'text' => $output, 'noparse' => true, 'isHTML' => true ];
    }
}
