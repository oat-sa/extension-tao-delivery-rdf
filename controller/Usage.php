<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2024 (original work) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 *
 *
 */

namespace oat\taoDeliveryRdf\controller;
use oat\tao\model\datatable\implementation\DatatableRequest;

/**
 * Class Usage
 * @package oat\taoDeliveryRdf\controller
 */
class Usage extends \tao_actions_CommonModule
{

    public function index()
        {
            $this->setView('Usage/index.tpl');
        }

    public function getSessionsDataMock()
    {
        $mock = '{
                              "success": true,
                              "page": 1,
                              "total": 1,
                              "totalCount": 10,
                              "records": 10,
                              "data": [
                              {
                                  "label": "Elegant Wooden Bike",
                                  "resourceId": "80128984-3dde-475b-b5b0-81d9da5c26f1",
                                  "publicationTime": "1976-07-24T12:52:17.452Z"
                              },
                              {
                                  "label": "Awesome Rubber Gloves",
                                  "resourceId": "99abbca4-97f0-49c1-9b6f-8201a6599c08",
                                  "publicationTime": "1972-02-15T14:13:17.356Z"
                              },
                              {
                                  "label": "Ergonomic Plastic Shoes",
                                  "resourceId": "fb1bb8b4-c74a-40f4-9dbd-6b95d8e70768",
                                  "publicationTime": "1981-07-04T09:44:31.861Z"
                              },
                              {
                                  "label": "Awesome Concrete Car",
                                  "resourceId": "3ddcfcac-f62f-4886-9d5c-f8db1b963e2f",
                                  "publicationTime": "2014-01-14T13:20:57.985Z"
                              },
                              {
                                  "label": "Licensed Bronze Towels",
                                  "resourceId": "3489b2ca-8dc3-4441-8e5c-9ecc1e1b5338",
                                  "publicationTime": "1974-09-04T16:57:56.162Z"
                              },
                              {
                                  "label": "Electronic Frozen Pizza",
                                  "resourceId": "c46fddf4-7931-4c0a-84e6-876677ad9f0c",
                                  "publicationTime": "2021-10-11T17:08:51.358Z"
                              },
                              {
                                  "label": "Fantastic Bronze Cheese",
                                  "resourceId": "7c1e652d-1d5e-4960-a344-f46f89c19e6d",
                                  "publicationTime": "1979-12-11T08:47:46.768Z"
                              },
                              {
                                  "label": "Gorgeous Steel Cheese",
                                  "resourceId": "bc223e3f-e10a-483f-be0d-bba315821d90",
                                  "publicationTime": "1994-01-16T07:32:49.898Z"
                              },
                              {
                                  "label": "Generic Bronze Hat",
                                  "resourceId": "8c33a124-ad33-44aa-901f-2fd85bd058c6",
                                  "publicationTime": "2022-03-22T14:36:57.347Z"
                              },
                              {
                                  "label": "Small Plastic Ball",
                                  "resourceId": "23e660bd-144b-40f9-8c4d-cac44c8ea1bf",
                                  "publicationTime": "1986-02-16T20:58:53.994Z"
                              }
                          ]
                      }';
        $this->returnJson(json_decode($mock));
    }
    public function getTestsDataMock()
    {
    $mock = '{
               "success": true,
               "page": 1,
               "total": 1,
               "totalCount": 10,
               "records": 10,
               "data": [
                 {
                   "label": "Intelligent Concrete Towels",
                   "location": "Nepal",
                   "publicationTime": "1989-02-22T08:14:02.887Z",
                   "link": "https://crooked-appliance.info"
                 },
                 {
                   "label": "Incredible Bronze Fish",
                   "location": "Vanuatu",
                   "publicationTime": "2007-06-13T20:20:22.696Z",
                   "link": "https://upbeat-pick.name"
                 },
                 {
                   "label": "Handcrafted Rubber Keyboard",
                   "location": "Bangladesh",
                   "publicationTime": "1985-03-01T08:51:10.026Z",
                   "link": "https://delightful-seep.info/"
                 },
                 {
                   "label": "Tasty Plastic Tuna",
                   "location": "Greenland",
                   "publicationTime": "1984-07-28T19:19:01.544Z",
                   "link": "https://steep-privilege.net"
                 },
                 {
                   "label": "Small Soft Hat",
                   "location": "Sao Tome and Principe",
                   "publicationTime": "1998-07-29T08:28:18.721Z",
                   "link": "https://comfortable-wampum.net/"
                 },
                 {
                   "label": "Generic Soft Keyboard",
                   "location": "South Africa",
                   "publicationTime": "2016-06-14T07:50:39.615Z",
                   "link": "https://jagged-fool.info/"
                 },
                 {
                   "label": "Modern Steel Cheese",
                   "location": "Denmark",
                   "publicationTime": "2020-06-09T13:01:45.715Z",
                   "link": "https://good-natured-legume.com"
                 },
                 {
                   "label": "Practical Fresh Hat",
                   "location": "Western Sahara",
                   "publicationTime": "1975-01-07T15:50:27.207Z",
                   "link": "https://portly-team.name"
                 },
                 {
                   "label": "Recycled Metal Keyboard",
                   "location": "Guam",
                   "publicationTime": "1992-11-18T01:07:48.003Z",
                   "link": "https://heartfelt-canteen.name"
                 },
                 {
                   "label": "Intelligent Frozen Ball",
                   "location": "Holy See (Vatican City State)",
                   "publicationTime": "1991-02-24T12:06:22.769Z",
                   "link": "https://other-mid-course.name/"
                 }
               ]
             }';
     $this->returnJson(json_decode($mock));
    }
}
