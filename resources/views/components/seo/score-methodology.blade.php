@props(['report'])

<p {{ $attributes }}>
    @if(in_array($report['scoring_version'] ?? null, ['weighted-v1', 'weighted-v2', 'weighted-v3', \App\Support\Seo\ReportScores::VERSION], true))
        Weighted score: subchecks earn 100% for a pass, 25% for a warning and 0% for an error.
        @if(in_array($report['scoring_version'] ?? null, ['weighted-v3', \App\Support\Seo\ReportScores::VERSION], true))
            A group with an error earns 0%; a group with a warning is capped at 25%. Passing housekeeping checks cannot cancel out a problem in the same group.
        @else
            This saved report averages subchecks without group severity caps. Run a new check to use the current scoring model.
        @endif
        Groups are weighted by importance: very important 8, important 4, low importance 2, nice to have 1. Category weights are Meta data 30%, Page quality 25%, Others 15%, Link structure 15% and Server configuration 15%. Only the categories selected for this check contribute to the score.
        @if(in_array($report['scoring_version'] ?? null, ['weighted-v2', 'weighted-v3', \App\Support\Seo\ReportScores::VERSION], true))
            All redirect checks count toward the score; unverified redirects earn no passing credit and remain labeled unverified. Unverified informational checks and unscored probes are excluded.
        @else
            Unverified checks and unscored probes are excluded. Run a new check to include unverified redirects in the denominator.
        @endif
        @if(($report['scoring_version'] ?? null) === \App\Support\Seo\ReportScores::VERSION)
            Crawlability is one combined check of indexing directives and robots.txt access; blocked or unverified crawlability earns no credit.
        @endif
        Unavailable categories are omitted and the remaining weights are normalized. External metrics do not affect the score.
    @else
        This saved report uses the original scoring method: equally weighted subchecks with 50% credit for warnings. Run a new check to use the weighted scoring model.
    @endif
    This is our single-page assessment, not a ranking prediction. Other SEO tools use different checks and scoring formulas.
</p>
