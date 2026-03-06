# Card View

An opt-in alternative to the default table row layout that displays results as a grid of cards.

## Basic Setup

Override `cardLayout()` in any `TableBuilder` or `QueryBuilder` component to enable the card view toggle:

```php
use ACTTraining\QueryBuilder\Support\CardLayout;

class CoursesTable extends TableBuilder
{
    public function cardLayout(): ?CardLayout
    {
        return CardLayout::make()
            ->columns(3)
            ->view('courses.card');
    }
}
```

This adds the grid/list toggle buttons to the toolbar. Without overriding `cardLayout()`, nothing changes — the toggle is hidden by default.

## Card Blade View

Create the Blade partial referenced in `->view()`. It receives `$row` (the Eloquent model) and `$imageUrl` (resolved image URL or null):

```blade
{{-- resources/views/courses/card.blade.php --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    @if($imageUrl)
        <div class="aspect-video overflow-hidden">
            <img src="{{ $imageUrl }}" alt="" class="w-full h-full object-cover" />
        </div>
    @endif

    <div class="p-4">
        <p class="text-sm text-orange-500 font-medium">{{ $row->category->name }}</p>
        <h3 class="text-lg font-bold text-gray-900 mt-1">{{ $row->title }}</h3>

        <div class="mt-3 space-y-1 text-sm text-gray-600">
            <p>{{ $row->duration }} hours</p>
            <p>{{ $row->location }}</p>
            <p>{{ $row->delivery_method }}</p>
        </div>
    </div>
</div>
```

## CardLayout Options

| Method | Description | Default |
|--------|-------------|---------|
| `->columns(int)` | Number of grid columns at `lg` breakpoint | `3` |
| `->image(string)` | Dot-notation key for the image URL on the model (e.g. `'photo_url'` or `'media.url'`) | `null` (no image) |
| `->placeholder(string)` | Fallback image URL when the model's image is null | `null` |
| `->view(string)` | Blade view to render each card | `'query-builder::card'` (basic default) |

## Examples

### Cards with images and a placeholder

```php
public function cardLayout(): ?CardLayout
{
    return CardLayout::make()
        ->image('featured_image_url')
        ->placeholder('/img/placeholder.jpg')
        ->columns(3)
        ->view('courses.card');
}
```

### Cards without images (text-only)

```php
public function cardLayout(): ?CardLayout
{
    return CardLayout::make()
        ->columns(4)
        ->view('contacts.card');
}
```

### Using the default card template

No custom view needed — renders image + model ID:

```php
public function cardLayout(): ?CardLayout
{
    return CardLayout::make()
        ->image('avatar_url');
}
```

## What still works in card mode

- **Pagination** — unchanged
- **Search** — unchanged
- **Filters / criteria** — unchanged
- **Sorting** — applied at the query level, so cards render in sorted order
- **Row click** — if `isClickable()` is true, clicking a card triggers the same action as clicking a table row
