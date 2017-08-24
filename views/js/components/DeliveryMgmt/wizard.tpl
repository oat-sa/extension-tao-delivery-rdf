<div class="wizard">

    <!-- input -->
    <div class="value">
        <input name="delivery" placeholder="{{__ 'Select the test you would like to publish'}}" value="">
        <span class="icon-down"></span>
    </div>

    <!-- dropdown -->
    <div class="dropdown">
        <div class="search">
            <i class="search icon"></i>
            <input type="text" placeholder="{{__ 'Search tests...'}}">
        </div>
        <div class="divider"></div>
        <div class="menu">
            {{#each tests}}
            <div class="item" data-value="{{this.id}}">{{this.text}}</div>
            {{/each}}
        </div>
    </div>

</div>
