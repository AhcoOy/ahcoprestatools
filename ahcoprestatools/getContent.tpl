
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<!-- <link rel="stylesheet" href="/resources/demos/style.css">-->
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<script>
    $(function () {
        $("#ahcoprestatoolstabs").tabs();
    });
</script>

<div id="ahcoprestatoolstabs">
    <ul>
        <li><a href="#tabs-1">Duplicate orders</a></li>
        <li><a href="#tabs-2">Database Tables and structures</a></li>
        <li><a href="#tabs-3">Debug</a></li>
    </ul>
    <div id="tabs-1">
        <h1>Duplicate Orders</h1>


        {foreach  from=$deleteSqls  key=nr  item=$deleteSql} 
            <p>
                {$deleteSql}
            </p>
        {/foreach}
        <table class="table order">
            <tr>
                <th>
                    Order Id
                </th>
                <th>
                    Reference
                </th>
                <th>
                    Customer
                </th>

                <th>
                    Total Paid Tax Incl
                </th>
                <th>
                    Order Status
                </th>
                <th>
                    Payment
                </th>
                <th>
                    Date Add
                </th>
                <th>
                    Action
                </th>
            </tr>

            {assign var="id_cart" value="0"}
            {foreach  from=$duplicateOrders  key=nr  item=$duplicateOrder} 
                {if  $duplicateOrder.id_cart != $id_cart}
                    <tr>
                        <td colspan="8" >
                    <center> Cart ID {$duplicateOrder.id_cart}</center>
                    </td>

                    </tr>
                    {assign var="id_cart" value=$duplicateOrder.id_cart}
                {/if}

                <tr>
                    <td>
                        {$duplicateOrder.id_order}
                    </td>
                    <td>
                        {$duplicateOrder.reference}
                    </td>
                    <td>
                        {$duplicateOrder.customer}
                    </td>
                    <td>
                        {$duplicateOrder.total_paid_tax_incl}
                    </td>
                    <td  style="background-color:  {$duplicateOrder.color}">
                        {$duplicateOrder.osname}
                    </td>
                    <td>
                        {$duplicateOrder.payment}
                    </td>
                    <td>
                        {$duplicateOrder.date_add}
                    </td>

                    <td>
                        <form method="POST" onsubmit="return confirm('Delete order {$duplicateOrder.id_order}. Are you sure?');" >
                            <input type="hidden" value="{$duplicateOrder.id_order}" name="id_order" >
                            <input type="submit" name="deleteOrder" value="Delete Order {$duplicateOrder.id_order}" >
                        </form>
                    </td>
                </tr>
            {/foreach}

        </table>
    </div>
    <div id="tabs-2">
        <table>
            <tr>
                <th>
                    Table Name
                </th>
                <th>
                    Field
                </th>
                <th>
                    Type
                </th>
                <th>
                    Null
                </th>
                <th>
                    Key
                </th>
                <th>
                    Default
                </th>
                <th>
                    Extra
                </th>
            </tr>
            {foreach  from=$tablesStructrures  key=nr  item=$tablesStructrure} 
                <tr>
                    <td>
                        {$tablesStructrure.dbTableName}
                    </td>
                    <th colspan="6">
                        &nbsp;
                        </td>
                </tr>
                {foreach  from=$tablesStructrure.dbTableStructure  key=nr  item=dbColTableStructure}
                    <tr>
                        <td>
                            &nbsp;
                        </td>
                        <td>
                            {$dbColTableStructure.Field}
                        </td>
                        <td>
                            {$dbColTableStructure.Type} 
                        </td>
                        <td>
                            {$dbColTableStructure.Null}   
                        </td>
                        <td>
                            {$dbColTableStructure.Key}   
                        </td>
                        <td>
                            {$dbColTableStructure.Default}   
                        </td>
                        <td>
                            {$dbColTableStructure.Extra}   
                        </td>
                    </tr>
                {/foreach}
            {/foreach}
        </table>
    </div>
    <div id="tabs-3">
        <pre>
            {$debugLogs}
        </pre>
    </div>
</div>