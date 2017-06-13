<div class="container main" role="main">
    <h1>$Title</h1>

    $SearchForm

    <% if $Query %>
        <p class="search-query">You searched for &quot;{$Query}&quot;</p>
    <% end_if %>

    <% if $Results %>
        <ul class="search-results">
            <% loop $Results %>
            <li>
                <h4 class="search-result">
                    <a class="search-result-link" href="$Link">
                        <% if $MenuTitle %>
                        $MenuTitle
                        <% else %>
                        $Title
                        <% end_if %>
                    </a>
                </h4>
                <% if $Content %>
                    <p class="search-result-summary">$Content.LimitWordCountXML</p>
                <% end_if %>
                <a class="search-result-readmore" href="$Link" title="Read more about &quot;{$Title}&quot;">Read more about &quot;{$Title}&quot;...</a>
            </li>
            <% end_loop %>
        </ul>
    <% else %>
        <p>Sorry, your search query did not return any results.</p>
    <% end_if %>

    <% if $Results.MoreThanOnePage %>
        <div id="search-result-pages">
            <div class="search-result-pagination">
                <% if $Results.NotFirstPage %>
                    <a class="search-result-pagination-prev" href="$Results.PrevLink" title="View the previous page">&larr;</a>
                <% end_if %>
                <span>
                    <% loop $Results.Pages %>
                        <% if $CurrentBool %>
                            $PageNum
                        <% else %>
                            <a href="$Link" title="View page number $PageNum" class="go-to-page">$PageNum</a>
                        <% end_if %>
                    <% end_loop %>
                </span>
                <% if $Results.NotLastPage %>
                    <a class="search-result-pagination-next" href="$Results.NextLink" title="View the next page">&rarr;</a>
                <% end_if %>
            </div>
            <p>Page $Results.CurrentPage of $Results.TotalPages</p>
        </div>
    <% end_if %>
</div>
