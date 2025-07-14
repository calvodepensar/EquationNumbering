# EquationNumbering
*EquationNumbering* is a simple MediaWiki extension that provides two new `autoeq` and `refeq` parser functions. These functions allow automatic numbering of mathematical expressions, resembling the LaTeX/amsmath *equation* environment.

## Features
* *Auto-numbering*. Automatically assigns sequential numbers to equations on a page.
* *References*. Generates clickable links to any numbered equation using a unique label.

## Installation
1. *Download Files*. Place the `EquationNumbering` directory inside your MediaWiki `extensions/` folder.

2. *Activate Extension*. Add the following line to your `LocalSettings.php` file:
    ```php
    wfLoadExtension( 'EquationNumbering' );
    ```

4. *Check version*. Navigate to the `Special:Version` page on your wiki; you should see `EquationNumbering` listed under `Installed extensions`, and `autoeq` and `ref_equation` under `Parser function hooks`.

## Usage
The extension provides two new parser functions: `{{#autoeq:<expr>|<label}}` and `{{#refeq:<label>}}`.

### Numbering an Equation
Use `autoeq` to display and number an equation, for example:

```wikitext
The Pythagorean theorem is a fundamental relation in Euclidean geometry:
{{#autoeq: <math>a^2 + b^2 = c^2</math> | eq:pythagoras }}
```

### Referencing an Equation
Use `refeq` to create a clickable link to an equation you have already labeled, for example:

```wikitext
As we can see in equation {{#refeq: eq:pythagoras }}, the sum of the squares of the two shorter sides of a right triangle is equal to the square of the hypotenuse.
```

### Styling
The output can be styled formatting the following CSS classes:
* `equation-container`.
* `equation-expr`.
* `equation-number`.
* `equation-ref`.

For example, the following code, added to `MediaWiki:Commons.css`, would cause the mathematical expression to be shown centred in the viewport, with the numbering justified to the right:

```css
.equation-container {
	display: flex;
	align-items: center;
}

.equation-expr{
	flex: auto;
	text-align: center;
}
```

### Error handling
If `refeq` references to a non-existent label, an error message will be displayed instead.

### Acknowledgments
The extension has been developed within the development framework of the glossaLAB platform (www.glossalab.org), under support of the projects "CLINFO-CM: Clear and inclusive information for digital transformation" (PHS-2024/PH-HUM-313, Comunidad de Madrid), and "glossaLAB.dixit: Development of a federated system of indexed publications oriented to the transdisciplinary clarification of knowledge and its qualification assisted by hybrid artificial intelligence" (INCYT ref.91870000.0000.390371, UPSE & glossaLAB consortium).
