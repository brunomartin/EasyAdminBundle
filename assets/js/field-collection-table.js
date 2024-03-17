const eaCollectionHandler = function (event) {
    document.querySelectorAll('button.field-collection-table-add-button').forEach((addButton) => {
        const collection = addButton.closest('[data-ea-collection-table-field]');

        if (!collection || collection.classList.contains('processed')) {
            return;
        }

        EaCollectionProperty.handleAddButton(addButton, collection);
        EaCollectionProperty.updateCollectionItemCssClasses(collection);
    });

    document.querySelectorAll('button.field-collection-table-delete-button').forEach((deleteButton) => {
        deleteButton.addEventListener('click', () => {
            const collection = deleteButton.closest('[data-ea-collection-table-field]');
            const item = deleteButton.closest('.field-collection-table-item');

            item.remove();
            document.dispatchEvent(new Event('ea.collection.item-removed'));

            EaCollectionProperty.updateCollectionItemCssClasses(collection);
        });
    });
}

window.addEventListener('DOMContentLoaded', eaCollectionHandler);
document.addEventListener('ea.collection.item-added', eaCollectionHandler);

const EaCollectionProperty = {
    handleAddButton: (addButton, collection) => {
        addButton.addEventListener('click', function() {
            const isArrayCollection = collection.classList.contains('field-array');
            // Use a counter to avoid having the same index more than once
            let numItems = parseInt(collection.dataset.numItems);

            const formTypeNamePlaceholder = collection.dataset.formTypeNamePlaceholder;
            const labelRegexp = new RegExp(formTypeNamePlaceholder + 'label__', 'g');
            const nameRegexp = new RegExp(formTypeNamePlaceholder, 'g');

            let newItemHtml = collection.dataset.prototype
                .replace(labelRegexp, ++numItems)
                .replace(nameRegexp, numItems);

            collection.dataset.numItems = numItems;

            const newItemInsertionSelector = '.ea-form-collection-table-items table > tbody';
            const collectionItemsWrapper = collection.querySelector(newItemInsertionSelector);

            collectionItemsWrapper.insertAdjacentHTML('beforeend', newItemHtml);

            // Execute JS scripts embedded in prototype
            const collectionItems = collectionItemsWrapper.querySelectorAll('.field-collection-table-item');
            const lastElement = collectionItems[collectionItems.length - 1];
            lastElement.querySelectorAll('script').forEach(script => eval(script.innerHTML));

            document.dispatchEvent(new Event('ea.collection.item-added'));
        });

        collection.classList.add('processed');
    },

    updateCollectionItemCssClasses: (collection) => {
        if (null === collection) {
            return;
        }

        const collectionItems = collection.querySelectorAll('.field-collection-table-item');
        collectionItems.forEach((item) => item.classList.remove('field-collection-table-item-first', 'field-collection-table-item-last'));

        const firstElement = collectionItems[0];
        if (undefined === firstElement) {
            return;
        }
        firstElement.classList.add('field-collection-table-item-first');

        const lastElement = collectionItems[collectionItems.length - 1];
        if (undefined === lastElement) {
            return;
        }
        lastElement.classList.add('field-collection-table-item-last');
    }
};
