<div class="wizard">

    <!-- input -->
    <div class="input">
        <input name="delivery" placeholder="{{__ 'Select the test you would like to publish'}}" value="" data-value="" readonly="readonly">
        <span class="icon-down"></span>
    </div>

    <!-- dropdown -->
    <div class="dropdown">
        <div class="search">
            <input type="text" placeholder="{{__ 'Search tests...'}}">
            <span class="icon-find"></span>
        </div>
        <div class="divider"></div>
        <div class="menu">
            {{#each tests}}
            <div class="item" data-value="{{this.id}}" data-text="{{this.text}}">
                <span class="icon-test"></span>
                {{this.text}}
            </div>
            {{/each}}
        </div>
    </div>

</div>
