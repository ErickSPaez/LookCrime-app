@if ($paginator->hasPages())
    <nav class="lc-register-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="lc-register-pagination__list">
            @if ($paginator->onFirstPage())
                <li aria-disabled="true">
                    <span class="lc-register-pagination__disabled">{!! __('pagination.previous') !!}</span>
                </li>
            @else
                <li>
                    <a class="lc-register-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        {!! __('pagination.previous') !!}
                    </a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li aria-disabled="true">
                        <span class="lc-register-pagination__disabled">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page">
                                <span class="lc-register-pagination__active">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a class="lc-register-pagination__link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li>
                    <a class="lc-register-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        {!! __('pagination.next') !!}
                    </a>
                </li>
            @else
                <li aria-disabled="true">
                    <span class="lc-register-pagination__disabled">{!! __('pagination.next') !!}</span>
                </li>
            @endif
        </ul>

        <div class="lc-register-pagination__summary">
            {{ __('pagination.showing', [
                'first' => $paginator->firstItem(),
                'last' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ]) }}
        </div>
    </nav>
@endif
