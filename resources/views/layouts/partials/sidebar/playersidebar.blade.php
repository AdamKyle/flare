<ul id="sidebarnav">
    <li><a href={{route('game')}}>Home</a></li>
    <li class="nav-small-cap">Character Management</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="mdi mdi-gauge"></i><span class="hide-menu">Character <span class="label label-rounded label-success">5</span></span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="index.html">Character Sheet</a></li>
            <li><a href="index2.html">Inventory</a></li>
            <li><a href="index2.html">Skills</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="mdi mdi-gauge"></i><span class="hide-menu">Crafting <span class="label label-rounded label-success">5</span></span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="index.html">Craft</a></li>
            <li><a href="index2.html">Destroy</a></li>
            <li><a href="index2.html">Crafting Skills</a></li>
        </ul>
    </li>
    <li class="nav-devider"></li>
    <li class="nav-small-cap">Manage Kindoms</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="mdi mdi-file"></i><span class="hide-menu">Kingdoms</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="form-basic.html">Dashboard</a></li>
            <li><a href="form-basic.html">Battle Reports</a></li>
            <li><a href="form-basic.html">Raid Reports</a></li>
            <li><a href="form-basic.html">Trade Reports</a></li>
        </ul>
    </li>
    <li class="nav-devider"></li>
    <li class="nav-small-cap">Manage Quests</li>
    <li>
        <a class="has-arrow " href="#" aria-expanded="false"><i class="mdi mdi-book-multiple"></i><span class="hide-menu">Quests</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="layout-single-column.html">Active Quests</a></li>
            <li><a href="layout-fix-header.html">Completed Quests</a></li>
            <li><a href="layout-fix-sidebar.html">Quest Items</a></li>
        </ul>
    </li>
    <li class="nav-devider"></li>
    <li class="nav-small-cap">Buy/Sell Items</li>
    <li>
        <a class="has-arrow " href="#" aria-expanded="false"><i class="mdi mdi-book-multiple"></i><span class="hide-menu">Shop</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href={{route('game.shop.buy')}}>Buy</a></li>
            <li><a href={{route('game.shop.sell')}}>Sell</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow " href="#" aria-expanded="false"><i class="mdi mdi-book-multiple"></i><span class="hide-menu">Market</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="layout-single-column.html">Buy</a></li>
            <li><a href="layout-fix-header.html">Sell</a></li>
        </ul>
    </li>
</ul>
