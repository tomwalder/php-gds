<?php
/**
 * Copyright 2021 Tom Walder
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use GDS\Mapper;
use GDS\Mapper\RESTv1;

/**
 * Tests for TimeZone formatting
 *
 * The REST API supports UTZ data in & out.
 * https://cloud.google.com/datastore/docs/reference/data/rest/v1/projects/runQuery#Value
 *
 * Specifically...
 *
 * A timestamp in RFC3339 UTC "Zulu" format, accurate to nanoseconds. Example: "2014-10-02T15:01:23.045123456Z".
 *
 * @author Tom Walder <twalder@gmail.com>
 */
class TimeZoneTest extends \PHPUnit\Framework\TestCase {

    const FORMAT_YMDHIS = 'Y-m-d H:i:s';

    const DTM_KNOWN_8601 = '2004-02-12T15:19:21+00:00';

    /**
     * Validate that creating datetime objects from 'U.u' format always results in UTC TZ
     */
    public function testCreateDateTimeFromFormatZone()
    {
        $str_existing_tz = date_default_timezone_get();
        date_default_timezone_set('America/Cayenne');

        // New datetimes should be in the current timezone
        $obj_dtm = new DateTime();
        $this->assertEquals('America/Cayenne', $obj_dtm->getTimezone()->getName());

        // Using timestamp should be in UTC (or '+00:00')
        $obj_dtm = new DateTime('@1625652400');
        $this->assertTrue(in_array($obj_dtm->getTimezone()->getName(), [Mapper::TZ_UTC, Mapper::TZ_UTC_OFFSET]));

        // Using string with zone
        $obj_dtm = new DateTime('2004-02-12T15:19:21+00:00');
        $this->assertTrue(in_array($obj_dtm->getTimezone()->getName(), [Mapper::TZ_UTC, Mapper::TZ_UTC_OFFSET]));

        // Using string with zone
        $obj_dtm = new DateTime('2004-02-12T15:19:21+04:00');
        $this->assertEquals('+04:00', $obj_dtm->getTimezone()->getName());

        // Using 'U.u' should be in UTC (or '+00:00')
        $obj_dtm = DateTime::createFromFormat(Mapper::DATETIME_FORMAT_UDOTU, '1625652400.123456');
        $this->assertTrue(in_array($obj_dtm->getTimezone()->getName(), [Mapper::TZ_UTC, Mapper::TZ_UTC_OFFSET]));

        // Using 'U.u' should be in UTC (or '+00:00')
        $obj_dtm = DateTime::createFromFormat(Mapper::DATETIME_FORMAT_UDOTU, '1625652400.000000');
        $this->assertTrue(in_array($obj_dtm->getTimezone()->getName(), [Mapper::TZ_UTC, Mapper::TZ_UTC_OFFSET]));

        // Using 'U.u' should be in UTC (or '+00:00')
        $obj_dtm = DateTime::createFromFormat(Mapper::DATETIME_FORMAT_UDOTU, '1625652400.0');
        $this->assertTrue(in_array($obj_dtm->getTimezone()->getName(), [Mapper::TZ_UTC, Mapper::TZ_UTC_OFFSET]));

        // Too many DP of data should fail
        $this->assertFalse(DateTime::createFromFormat(Mapper::DATETIME_FORMAT_UDOTU, '1625652400.999999999'));

        // And back to local TZ use cases
        $obj_dtm = new DateTime('2021-01-05 15:34:23');
        $this->assertEquals('America/Cayenne', $obj_dtm->getTimezone()->getName());
        $obj_dtm = new DateTime('2021-01-05');
        $this->assertEquals('America/Cayenne', $obj_dtm->getTimezone()->getName());
        $obj_dtm = new DateTime('01:30');
        $this->assertEquals('America/Cayenne', $obj_dtm->getTimezone()->getName());
        $obj_dtm = new DateTime('now');
        $this->assertEquals('America/Cayenne', $obj_dtm->getTimezone()->getName());
        $obj_dtm = new DateTime('yesterday');
        $this->assertEquals('America/Cayenne', $obj_dtm->getTimezone()->getName());

        // Reset the timezone
        date_default_timezone_set($str_existing_tz);
    }

    /**
     * Validate understanding and truncation of RFC3339 UTC "Zulu" format
     */
    public function testMicroConversions()
    {
        // This is the example from the Google API docs, with more accuracy than PHP can handle
        $str_rfc_3339 = '2014-10-02T15:01:23.045123456Z';

        // Some expected conversions
        $str_php_micros = '1412262083.045123';
        $str_php_c = '2014-10-02T15:01:23+00:00';
        $str_php_rfc_3339 = '2014-10-02T15:01:23.045123Z';

        $obj_mapper = new RESTv1();
        $obj_dtm = $obj_mapper->buildLocalisedDateTimeObjectFromUTCString($str_rfc_3339);

        $this->assertEquals($str_php_micros, $obj_dtm->format(Mapper::DATETIME_FORMAT_UDOTU));
        $this->assertEquals($str_php_c, $obj_dtm->format('c'));
        $this->assertEquals($str_php_rfc_3339, $obj_dtm->format(RESTv1::DATETIME_FORMAT_ZULU));
    }

    /**
     * Validate understanding and truncation of RFC3339 UTC "Zulu" format
     */
    public function testMicroConversionsWithTimezone()
    {
        $str_existing_tz = date_default_timezone_get();
        date_default_timezone_set('America/Cayenne');

        // This is the example from the Google API docs, with more accuracy than PHP can handle
        $str_rfc_3339 = '2014-10-02T15:01:23.045123456Z';

        // Some expected conversions
        $str_php_micros = '1412262083.045123';
        $str_php_c = '2014-10-02T12:01:23-03:00';

        $obj_mapper = new RESTv1();
        $obj_dtm = $obj_mapper->buildLocalisedDateTimeObjectFromUTCString($str_rfc_3339);

        $this->assertEquals('America/Cayenne', date_default_timezone_get());
        $this->assertEquals('America/Cayenne', $obj_dtm->getTimezone()->getName());

        $this->assertEquals($str_php_micros, $obj_dtm->format(Mapper::DATETIME_FORMAT_UDOTU));
        $this->assertEquals($str_php_c, $obj_dtm->format('c'));

        // Reset the timezone
        date_default_timezone_set($str_existing_tz);
    }

    /**
     * Confirm known behaviour - which is that unadjusted zulu formats are not equal
     *
     * @throws Exception
     */
    public function testZuluFormat()
    {
        $obj_tz_utc = new DateTimeZone('UTC');
        $obj_dtm1 = new DateTime('now', $obj_tz_utc);
        $str_utc_ts = $obj_dtm1->format(Mapper::DATETIME_FORMAT_UDOTU);

        $obj_tz_london = new DateTimeZone('Europe/London');
        $obj_dtm1->setTimezone($obj_tz_london);
        $str_london_ts = $obj_dtm1->format(Mapper::DATETIME_FORMAT_UDOTU);
        $str_london_zulu = $obj_dtm1->format(RESTv1::DATETIME_FORMAT_ZULU);

        $obj_tz_nyc = new DateTimeZone('America/New_York');
        $obj_dtm1->setTimezone($obj_tz_nyc);
        $str_nyc_ts = $obj_dtm1->format(Mapper::DATETIME_FORMAT_UDOTU);
        $str_nyc_zulu = $obj_dtm1->format(RESTv1::DATETIME_FORMAT_ZULU);

        // Timestamps always match
        $this->assertEquals($str_utc_ts, $str_london_ts);
        $this->assertEquals($str_utc_ts, $str_nyc_ts);

        // London and NYC never match in unadjusted zulu format
        $this->assertNotEquals($str_london_zulu, $str_nyc_zulu);
    }

    public function testZoneConversion()
    {
        $obj_tz_utc = new DateTimeZone('UTC');
        $obj_tz_nyc = new DateTimeZone('America/New_York');
        $obj_tz_xmas = new DateTimeZone('Indian/Christmas');

        $obj_dtm_utc = (new DateTime(self::DTM_KNOWN_8601))->setTimezone($obj_tz_utc);
        $obj_dtm_nyc = (new DateTime(self::DTM_KNOWN_8601))->setTimezone($obj_tz_nyc);
        $obj_dtm_xmas = (new DateTime(self::DTM_KNOWN_8601))->setTimezone($obj_tz_xmas);

        // Timestamps match
        $this->assertEquals($obj_dtm_utc->format('U'), $obj_dtm_nyc->format('U'));
        $this->assertEquals($obj_dtm_utc->format('U'), $obj_dtm_xmas->format('U'));

        // Unadjusted Zulu mismatch
        $this->assertNotEquals($obj_dtm_utc->format(RESTv1::DATETIME_FORMAT_ZULU), $obj_dtm_nyc->format(RESTv1::DATETIME_FORMAT_ZULU));
        $this->assertNotEquals($obj_dtm_utc->format(RESTv1::DATETIME_FORMAT_ZULU), $obj_dtm_xmas->format(RESTv1::DATETIME_FORMAT_ZULU));
        $this->assertNotEquals($obj_dtm_nyc->format(RESTv1::DATETIME_FORMAT_ZULU), $obj_dtm_xmas->format(RESTv1::DATETIME_FORMAT_ZULU));

        // Adjust value to UTC, then the outputs should match
        $str_zulu_utc_adjusted = $obj_dtm_utc->setTimezone($obj_tz_utc)->format(RESTv1::DATETIME_FORMAT_ZULU);
        $str_zulu_nyc_adjusted = $obj_dtm_nyc->setTimezone($obj_tz_utc)->format(RESTv1::DATETIME_FORMAT_ZULU);
        $str_zulu_xmas_adjusted = $obj_dtm_xmas->setTimezone($obj_tz_utc)->format(RESTv1::DATETIME_FORMAT_ZULU);
        $this->assertEquals($str_zulu_utc_adjusted, $str_zulu_nyc_adjusted);
        $this->assertEquals($str_zulu_utc_adjusted, $str_zulu_xmas_adjusted);

        // And confirm new UTC-based objects with these values match
        $obj_dtm_utc_from_zulu1 = DateTime::createFromFormat(RESTv1::DATETIME_FORMAT_ZULU, $str_zulu_utc_adjusted, $obj_tz_utc);
        $this->assertEquals(
            $obj_dtm_utc->format('U'),
            $obj_dtm_utc_from_zulu1->format('U')
        );
        $this->assertEquals(
            $obj_dtm_utc->format(self::FORMAT_YMDHIS),
            $obj_dtm_utc_from_zulu1->format(self::FORMAT_YMDHIS)
        );
        $this->assertEquals(
            $obj_dtm_utc->getTimezone()->getName(),
            $obj_dtm_utc_from_zulu1->getTimezone()->getName()
        );
    }

}