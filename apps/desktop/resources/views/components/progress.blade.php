@props(['percent'])
<div
    role="progressbar"
    aria-valuenow="{{ $percent }}"
    aria-valuemin="0"
    aria-valuemax="100"
    style="height: .375rem; width: 100%; border-radius: 9999px; background: rgb(var(--gray-200, 229 231 235) / 1); overflow: hidden;"
    class="dark:bg-white/10"
>
    <div style="height: 100%; width: {{ $percent }}%; border-radius: 9999px; background: var(--primary-500, #6366f1); transition: width .4s ease;"></div>
</div>
